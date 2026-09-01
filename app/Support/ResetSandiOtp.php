<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * KODE OTP UNTUK RESET KATA SANDI LEWAT APLIKASI ANDROID.
 *
 * =====================================================================
 * KENAPA WHATSAPP, BUKAN EMAIL
 * =====================================================================
 * Sekolah ini sudah punya jalur WhatsApp yang jalan dan dipercaya
 * (lihat App\Jobs\KirimNotifikasiAlfaWhatsapp), sementara surel guru
 * banyak yang jarang dibuka. Nomor tujuannya diambil dari kolom
 * `no_hp` milik akun itu sendiri — bukan dari yang diketik peminta —
 * supaya orang lain tidak bisa mengalihkan kode ke nomornya sendiri.
 *
 * Dikirim lewat PERANGKAT 1 Fonnte (`services.fonnte.token`, nomor
 * sekolah), perangkat yang sama yang memberi tahu orang tua soal siswa
 * Alfa. Perangkat 2 khusus pengingat ke guru dan sengaja tidak dipakai
 * di sini.
 *
 * =====================================================================
 * KENAPA LANGSUNG, BUKAN LEWAT QUEUE
 * =====================================================================
 * Notifikasi Alfa memang diantrikan, karena guru tidak boleh menunggu
 * jaringan luar saat menyimpan absensi. Di sini kebalikannya: pengguna
 * SEDANG menunggu kodenya, dan kalau `queue:work` kebetulan tidak
 * berjalan, kode itu tidak akan pernah terkirim tanpa ada yang tahu.
 * Menunggu beberapa detik jauh lebih baik daripada gagal diam-diam.
 *
 * =====================================================================
 * PENGAMANNYA
 * =====================================================================
 *   - Kode 6 angka, berumur 5 menit, disimpan di cache (bukan database)
 *     sehingga hilang sendiri.
 *   - Salah 5 kali membuat kodenya hangus — menebak 000000..999999
 *     karena itu tidak mungkin dilakukan dengan mencoba satu per satu.
 *   - Kode lama langsung tergantikan setiap kali yang baru diminta.
 */
class ResetSandiOtp
{
    /** Umur kode. Cukup untuk membuka WhatsApp, tidak cukup untuk ditebak. */
    public const UMUR_DETIK = 300;

    /** Salah sebanyak ini membuat kodenya hangus, harus minta yang baru. */
    public const MAKS_SALAH = 5;

    /**
     * Terbitkan kode baru dan kirimkan ke nomor WhatsApp milik akun ini.
     *
     * Mengembalikan false bila WhatsApp-nya gagal terkirim. Pemanggil
     * yang memutuskan apakah kegagalan itu ditampilkan kepada pengguna.
     */
    public static function kirim(User $user): bool
    {
        $nomor = self::normalisasiNomor((string) $user->no_hp);

        if ($nomor === '') {
            return false;
        }

        $kode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Kode baru selalu menggantikan yang lama, berikut hitungan
        // salahnya — kalau tidak, sisa hitungan dari permintaan
        // sebelumnya ikut menghanguskan kode yang baru saja dikirim.
        Cache::put(self::kunci($user), $kode, self::UMUR_DETIK);
        Cache::forget(self::kunciSalah($user));

        $menit = (int) (self::UMUR_DETIK / 60);

        $pesan = "*SIM-SPENGA*\n\n"
            ."Kode untuk mengatur ulang kata sandi Anda:\n\n"
            ."*{$kode}*\n\n"
            ."Berlaku {$menit} menit. Kata sandi Anda akan dikembalikan ke "
            ."setelan awal setelah kode ini dimasukkan.\n\n"
            .'Bukan Anda yang meminta? Abaikan pesan ini dan beri tahu Admin sekolah.';

        return self::kirimWhatsapp($nomor, $pesan);
    }

    /**
     * Apakah kode yang diketik cocok?
     *
     * Sekali cocok, kodenya langsung hangus — jadi satu kode hanya bisa
     * dipakai untuk satu kali reset.
     */
    public static function cocok(User $user, string $kode): bool
    {
        $tersimpan = Cache::get(self::kunci($user));

        if (! is_string($tersimpan) || $tersimpan === '') {
            return false;
        }

        $salah = (int) Cache::get(self::kunciSalah($user), 0);

        if ($salah >= self::MAKS_SALAH) {
            self::hapus($user);

            return false;
        }

        // hash_equals: lama pembandingan tidak boleh membocorkan seberapa
        // banyak angka depan yang sudah benar.
        if (! hash_equals($tersimpan, preg_replace('/[^0-9]/', '', $kode) ?? '')) {
            Cache::put(self::kunciSalah($user), $salah + 1, self::UMUR_DETIK);

            return false;
        }

        self::hapus($user);

        return true;
    }

    /** Hanguskan kode beserta hitungan salahnya. */
    public static function hapus(User $user): void
    {
        Cache::forget(self::kunci($user));
        Cache::forget(self::kunciSalah($user));
    }

    private static function kunci(User $user): string
    {
        return 'reset-sandi-otp:'.$user->id;
    }

    private static function kunciSalah(User $user): string
    {
        return 'reset-sandi-otp-salah:'.$user->id;
    }

    /**
     * Rapikan nomor ke format 62xxxx — sama dengan yang dipakai
     * App\Jobs\KirimNotifikasiAlfaWhatsapp, karena tujuannya perangkat
     * Fonnte yang sama.
     */
    private static function normalisasiNomor(string $nomor): string
    {
        $nomor = preg_replace('/[^0-9]/', '', $nomor) ?? '';

        if (str_starts_with($nomor, '0')) {
            $nomor = '62'.substr($nomor, 1);
        }

        return $nomor;
    }

    private static function kirimWhatsapp(string $nomor, string $pesan): bool
    {
        try {
            $response = Http::timeout(20)
                ->asForm()
                ->withHeaders(['Authorization' => config('services.fonnte.token')])
                ->post(config('services.fonnte.url'), [
                    'target' => $nomor,
                    'message' => $pesan,
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Kode reset sandi gagal dikirim: tidak bisa menghubungi Fonnte.', [
                'pesan' => $e->getMessage(),
            ]);

            return false;
        }

        // Fonnte sering menjawab HTTP 200 walau pesannya sendiri gagal
        // diproses, jadi status di badan JSON yang menentukan — bukan
        // kode HTTP-nya. Lihat catatan yang sama di job notifikasi Alfa.
        $body = $response->json() ?? [];
        $sukses = $response->successful() && (($body['status'] ?? false) === true);

        if (! $sukses) {
            Log::warning('Kode reset sandi ditolak Fonnte.', [
                'http' => $response->status(),
                'body' => $body,
            ]);
        }

        return $sukses;
    }
}
