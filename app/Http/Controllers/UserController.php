<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private const ROLES = ['admin', 'kepala_sekolah', 'kurikulum', 'guru', 'guru_bk', 'kesiswaan', 'tu'];

    public function index(Request $request)
    {
        $query = User::query()->when($request->role, fn ($q) => $q->where('role', $request->role));
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('nip', 'like', "%{$request->search}%");
            });
        }
        $users = $query->orderBy('name')->paginate(25)->withQueryString();
        return view('users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nip' => ['nullable', 'string', 'unique:users,nip'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', Rule::in(self::ROLES)],
            'no_hp' => ['nullable', 'string'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        User::create($validated);

        return back()->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nip' => ['nullable', 'string', 'unique:users,nip,' . $user->id],
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', Rule::in(self::ROLES)],
            'no_hp' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }
        $user->update($validated);

        return back()->with('success', 'Pengguna berhasil diperbarui.');
    }

    /**
     * Kembalikan kata sandi seorang pengguna ke setelan awal.
     *
     * Dipakai saat guru lupa kata sandinya dan tidak bisa memakai jalur
     * mandiri lewat aplikasi Android — mis. nomor WhatsApp-nya belum
     * terdaftar, atau nomornya sudah berganti. Sengaja TIDAK meminta
     * Admin mengarang kata sandi baru: yang perlu diucapkan lewat telepon
     * cukup satu kata yang sama untuk semua orang, dan pemiliknya wajib
     * segera menggantinya sendiri.
     *
     * Akun sendiri ditolak, mengikuti penjaga yang sama di destroy():
     * Admin yang mereset dirinya sendiri hanya akan mengunci diri di
     * belakang kata sandi yang mungkin ia lupa sudah berubah. Untuk
     * mengganti kata sandinya sendiri, tombol Edit sudah menyediakannya.
     */
    public function resetPassword(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            return back()->with('error', 'Untuk mengganti kata sandi Anda sendiri, gunakan tombol Edit.');
        }

        // Cast 'hashed' di model User yang mengubahnya menjadi hash.
        $user->forceFill(['password' => User::PASSWORD_DEFAULT])->save();

        return back()->with('success', "Kata sandi {$user->name} dikembalikan ke \""
            .User::PASSWORD_DEFAULT.'". Minta yang bersangkutan segera menggantinya setelah masuk.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'Tidak dapat menghapus admin terakhir yang tersisa.');
        }

        // (2026-08-28) Sejak penunjuk ke users dijadikan RESTRICT (migrasi
        // 2026_08_28_000006), menghapus guru yang masih punya jadwal /
        // mapping / penugasan wali kelas ditolak database. Dulu baris-baris
        // itu ikut terhapus diam-diam; sekarang ditolak, jadi harus lewat
        // penjaga yang menerangkan APA yang masih memakainya.
        return $this->hapusAtauGagalDenganPesan(
            $user,
            'Pengguna berhasil dihapus.',
            'Pengguna ini tidak dapat dihapus karena masih terhubung dengan data akademik. Nonaktifkan saja akunnya lewat tombol Edit agar riwayatnya tetap utuh'
        );
    }
}
