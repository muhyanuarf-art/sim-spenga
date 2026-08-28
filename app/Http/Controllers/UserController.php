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
