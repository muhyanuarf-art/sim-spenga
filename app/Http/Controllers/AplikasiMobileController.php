<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * JEMBATAN UNTUK APLIKASI ANDROID (Flutter).
 *
 * =====================================================================
 * CARA KERJANYA — DAN KENAPA TANPA TOKEN
 * =====================================================================
 * Aplikasi Android ini berbentuk: layar masuk NATIVE, lalu seluruh
 * aplikasi web ditampilkan di dalamnya lewat WebView. Yang dibutuhkan
 * karena itu bukan token API yang dipakai memanggil puluhan endpoint,
 * melainkan satu hal saja: cara memindahkan hasil login native menjadi
 * SESI WEB di dalam WebView.
 *
 * Alurnya:
 *
 *   1. Aplikasi mengirim email + kata sandi ke `POST /aplikasi/masuk`.
 *   2. Server memeriksa kredensial, keaktifan akun, dan PERANNYA.
 *   3. Bila lolos, server membalas JSON berisi nama, peran, dan sebuah
 *      TAUTAN MASUK-SEKALI yang ditandatangani dan berumur 60 detik.
 *   4. Aplikasi membuka tautan itu di WebView. Server memakainya untuk
 *      membuat sesi web seperti login biasa, lalu mengalihkan ke
 *      dashboard. Cookie sesinya tersimpan di WebView.
 *
 * Sanctum/Passport sengaja TIDAK dipakai. Keduanya berguna bila aplikasi
 * memanggil banyak endpoint JSON — di sini tidak ada satu pun. Menambah
 * pustaka, tabel token, dan masa berlaku yang harus diurus hanya untuk
 * satu langkah login adalah beban yang tidak dibayar oleh manfaatnya.
 *
 * Yang juga penting: aplikasi TIDAK PERNAH menyimpan kata sandi. Yang
 * disimpan hanya cookie sesi milik WebView, persis seperti membuka
 * aplikasi ini di peramban.
 *
 * =====================================================================
 * PENGAMAN TAUTAN MASUK-SEKALI
 * =====================================================================
 * Tautan itu adalah kunci: siapa pun yang memegangnya bisa masuk sebagai
 * pengguna tersebut. Karena itu dijaga berlapis:
 *
 *   - Ditandatangani APP_KEY (middleware `signed`) — tidak bisa dikarang.
 *   - Berumur 60 detik.
 *   - SEKALI PAKAI: nonce-nya dihapus begitu terpakai, jadi tautan yang
 *     sama tidak bisa diputar ulang walau tercatat di suatu log.
 *   - Hanya berlaku untuk peran yang diizinkan (diperiksa lagi saat
 *     tautannya dibuka, bukan hanya saat diterbitkan).
 */
class AplikasiMobileController extends Controller
{
    /**
     * Peran yang boleh memakai aplikasi Android.
     *
     * ADMIN sengaja tidak masuk daftar: pekerjaannya — mengelola akun,
     * mengunci periode, mengosongkan data, memegang token WhatsApp —
     * menuntut layar lebar dan ketelitian, dan beberapa di antaranya
     * tidak bisa dibatalkan. Portal orang tua juga tidak termasuk karena
     * memakai guard terpisah (`orangtua`); akunnya bahkan tidak berada di
     * tabel yang sama.
     */
    public const PERAN_DIIZINKAN = [
        'guru', 'guru_bk', 'kurikulum', 'kesiswaan', 'kepala_sekolah', 'tu',
    ];

    private const UMUR_TAUTAN_DETIK = 60;

    /**
     * Langkah 1 — layar masuk native mengirim kredensial ke sini.
     */
    public function masuk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email belum diisi.',
            'password.required' => 'Kata sandi belum diisi.',
        ]);

        // Guru lebih hafal NIP daripada alamat surel, jadi keduanya
        // diterima. Yang dicari tetap satu akun yang sama.
        $user = User::where('email', $data['email'])
            ->orWhere('nip', $data['email'])
            ->first();

        // Pesan gagalnya sengaja sama untuk "akun tidak ada" dan "sandi
        // salah" — membedakannya memberi tahu orang luar akun mana yang
        // benar-benar terdaftar.
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'pesan' => 'Email/NIP atau kata sandi salah.',
            ], 422);
        }

        if (! $user->is_active) {
            return response()->json([
                'pesan' => 'Akun Anda dinonaktifkan. Hubungi Admin sekolah.',
            ], 403);
        }

        if (! in_array($user->role, self::PERAN_DIIZINKAN, true)) {
            return response()->json([
                'pesan' => $user->role === 'admin'
                    ? 'Akun Admin tidak dapat masuk lewat aplikasi Android. Gunakan peramban di komputer.'
                    : 'Peran akun Anda tidak diizinkan memakai aplikasi ini.',
            ], 403);
        }

        return response()->json([
            'nama' => $user->name,
            'peran' => $user->roleLabel(),
            'url_masuk' => $this->tautanMasukSekali($user),
        ]);
    }

    /**
     * Langkah 2 — WebView membuka tautan ini; di sinilah sesi web dibuat.
     */
    public function masukOtomatis(Request $request, User $user)
    {
        $nonce = (string) $request->query('nonce');

        // Sekali pakai. Cache::pull mengambil sekaligus menghapus, jadi
        // dua permintaan bersamaan pun hanya satu yang berhasil.
        if ($nonce === '' || Cache::pull($this->kunciNonce($user, $nonce)) === null) {
            return $this->tolak('Tautan masuk sudah dipakai atau kedaluwarsa. Silakan masuk lagi dari aplikasi.');
        }

        // Diperiksa ULANG, bukan mengandalkan pemeriksaan saat tautannya
        // diterbitkan: dalam 60 detik itu akun bisa saja dinonaktifkan
        // atau perannya diubah admin.
        if (! $user->is_active || ! in_array($user->role, self::PERAN_DIIZINKAN, true)) {
            return $this->tolak('Akun ini tidak diizinkan memakai aplikasi Android.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    /** Terbitkan tautan masuk-sekali beserta nonce-nya. */
    private function tautanMasukSekali(User $user): string
    {
        $nonce = Str::random(40);

        Cache::put($this->kunciNonce($user, $nonce), true, now()->addSeconds(self::UMUR_TAUTAN_DETIK));

        return URL::temporarySignedRoute(
            'aplikasi.masuk-otomatis',
            now()->addSeconds(self::UMUR_TAUTAN_DETIK),
            ['user' => $user->id, 'nonce' => $nonce]
        );
    }

    private function kunciNonce(User $user, string $nonce): string
    {
        return 'masuk-aplikasi:'.$user->id.':'.$nonce;
    }

    /**
     * Penolakan ditampilkan sebagai halaman, bukan JSON: yang membukanya
     * adalah WebView, dan pengguna harus bisa membacanya.
     */
    private function tolak(string $pesan)
    {
        return response()->view('errors.aplikasi-masuk', ['pesan' => $pesan], 403);
    }
}
