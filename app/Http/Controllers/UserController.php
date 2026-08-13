<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private const ROLES = ['admin', 'kepala_sekolah', 'kurikulum', 'guru', 'guru_bk', 'orang_tua'];

    public function index(Request $request)
    {
        $query = User::query()->when($request->role, fn ($q) => $q->where('role', $request->role));
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('nip', 'like', "%{$request->search}%");
            });
        }
        $users = $query->with('anakAsuh')->orderBy('name')->paginate(25)->withQueryString();
        $siswaList = Siswa::with('kelas')->where('is_active', true)->orderBy('nama')->get();
        return view('users.index', compact('users', 'siswaList'));
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
            'anak_ids' => ['nullable', 'array'],
            'anak_ids.*' => ['exists:siswas,id'],
        ]);
        $anakIds = $validated['anak_ids'] ?? [];
        unset($validated['anak_ids']);

        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);

        if ($user->role === 'orang_tua' && $anakIds) {
            $user->anakAsuh()->sync($anakIds);
        }

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
            'anak_ids' => ['nullable', 'array'],
            'anak_ids.*' => ['exists:siswas,id'],
        ]);
        $anakIds = $validated['anak_ids'] ?? [];
        unset($validated['anak_ids']);

        $validated['is_active'] = $request->boolean('is_active', true);
        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }
        $user->update($validated);

        // Sinkronkan anak asuh hanya kalau rolenya orang_tua; kalau role
        // diubah jadi bukan orang_tua, lepas semua tautan anaknya.
        if ($user->role === 'orang_tua') {
            $user->anakAsuh()->sync($anakIds);
        } else {
            $user->anakAsuh()->sync([]);
        }

        return back()->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return back()->with('success', 'Pengguna berhasil dihapus.');
    }
}
