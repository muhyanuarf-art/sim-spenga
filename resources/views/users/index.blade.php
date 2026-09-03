@extends('layouts.app')
@section('title', 'Kelola Pengguna')

@section('content')
<div class="space-y-6" x-data="{ showForm: false }">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <form method="GET" class="flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama/NIP..." class="input max-w-xs">
            <select name="role" class="input" onchange="this.form.submit()">
                <option value="">Semua Role</option>
                <option value="admin" {{ request('role')=='admin'?'selected':'' }}>Admin</option>
                <option value="kepala_sekolah" {{ request('role')=='kepala_sekolah'?'selected':'' }}>Kepala Sekolah</option>
                <option value="kurikulum" {{ request('role')=='kurikulum'?'selected':'' }}>Kurikulum</option>
                <option value="guru" {{ request('role')=='guru'?'selected':'' }}>Guru</option>
                <option value="guru_bk" {{ request('role')=='guru_bk'?'selected':'' }}>Guru BK</option>
                <option value="kesiswaan" {{ request('role')=='kesiswaan'?'selected':'' }}>Kesiswaan</option>
                <option value="tu" {{ request('role')=='tu'?'selected':'' }}>Tata Usaha</option>
            </select>
            <button class="btn-outline">Cari</button>
        </form>
        <button @click="showForm = !showForm" class="btn-primary">+ Tambah Pengguna</button>
    </div>

    <div class="card p-5" x-show="showForm" x-cloak x-transition x-data="{ role: 'guru' }">
        <p class="font-bold text-slate-800 mb-4">Tambah Pengguna</p>
        <form method="POST" action="{{ route('users.store') }}" class="grid sm:grid-cols-3 gap-3 items-end">
            @csrf
            <input type="text" name="name" placeholder="Nama Lengkap" required class="input">
            <input type="text" name="nip" placeholder="NIP (opsional)" class="input">
            <input type="email" name="email" placeholder="Email" required class="input">
            <input type="password" name="password" placeholder="Password" required class="input">
            <select name="role" x-model="role" required class="input">
                <option value="guru">Guru</option>
                <option value="guru_bk">Guru BK</option>
                <option value="kesiswaan">Kesiswaan</option>
                <option value="tu">Tata Usaha</option>
                <option value="kurikulum">Kurikulum</option>
                <option value="kepala_sekolah">Kepala Sekolah</option>
                <option value="admin">Admin</option>
            </select>
            <input type="text" name="no_hp" placeholder="No. HP (opsional)" class="input">
            <button type="submit" class="btn-primary h-[38px]">Simpan</button>
        </form>
    </div>

    <div class="card p-5">
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th class="w-12 text-center">No</th><th>Nama</th><th>NIP</th><th>Email</th><th>Role</th><th>Status</th><th class="th-aksi">Aksi</th></tr></thead>
                @forelse($users as $u)
                <tbody x-data="{ editing: false, role: '{{ $u->role }}' }">
                    <tr x-show="!editing">
                        <td class="text-center text-slate-400">{{ $users->firstItem() + $loop->index }}</td>
                        <td class="font-medium">{{ $u->name }}</td>
                        <td>{{ $u->nip ?? '-' }}</td>
                        <td>{{ $u->email }}</td>
                        <td>
                            <span class="badge bg-brand-50 text-brand-700">{{ $u->roleLabel() }}</span>
                        </td>
                        <td>
                            @if($u->is_active)<span class="badge bg-emerald-50 text-emerald-700">Aktif</span>
                            @else<span class="badge bg-slate-100 text-slate-500">Nonaktif</span>@endif
                        </td>
                        <td class="td-aksi">
                            <div class="action-buttons">
                                <button type="button" @click="editing = true" class="btn-chip btn-chip-edit"><i class="fa-solid fa-pen mr-1.5"></i> Edit</button>

                                {{-- Reset kata sandi. Tidak ditampilkan pada baris
                                     akun sendiri: mengembalikan kata sandi sendiri ke
                                     setelan awal hanya menjebak, dan tombol Edit sudah
                                     menyediakan cara yang benar. Server tetap menolaknya
                                     juga, jadi ini semata-mata supaya tidak menggoda. --}}
                                @if(auth()->id() !== $u->id)
                                    <form method="POST" action="{{ route('users.reset-password', $u) }}"
                                          onsubmit="return confirm('Kembalikan kata sandi {{ $u->name }} ke setelan awal?\n\nKata sandi lamanya akan langsung tidak berlaku.')">
                                        @csrf
                                        <button class="btn-chip btn-chip-reset"><i class="fa-solid fa-key mr-1.5"></i> Reset Sandi</button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('users.destroy', $u) }}" onsubmit="return confirm('Hapus pengguna ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn-chip btn-chip-delete"><i class="fa-solid fa-trash mr-1.5"></i> Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr x-show="editing" x-cloak>
                        <td colspan="7" class="bg-brand-50/40">
                            <form method="POST" action="{{ route('users.update', $u) }}" class="grid sm:grid-cols-3 gap-3 items-end py-2">
                                @csrf @method('PUT')
                                <input type="text" name="name" value="{{ $u->name }}" placeholder="Nama Lengkap" required class="input">
                                <input type="text" name="nip" value="{{ $u->nip }}" placeholder="NIP (opsional)" class="input">
                                <input type="email" name="email" value="{{ $u->email }}" placeholder="Email" required class="input">
                                <input type="password" name="password" placeholder="Kosongkan jika tidak diubah" class="input">
                                <select name="role" x-model="role" required class="input">
                                    <option value="guru" {{ $u->role === 'guru' ? 'selected' : '' }}>Guru</option>
                                    <option value="guru_bk" {{ $u->role === 'guru_bk' ? 'selected' : '' }}>Guru BK</option>
                                    <option value="kesiswaan" {{ $u->role === 'kesiswaan' ? 'selected' : '' }}>Kesiswaan</option>
                                    <option value="tu" {{ $u->role === 'tu' ? 'selected' : '' }}>Tata Usaha</option>
                                    <option value="kurikulum" {{ $u->role === 'kurikulum' ? 'selected' : '' }}>Kurikulum</option>
                                    <option value="kepala_sekolah" {{ $u->role === 'kepala_sekolah' ? 'selected' : '' }}>Kepala Sekolah</option>
                                    <option value="admin" {{ $u->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                </select>
                                <input type="text" name="no_hp" value="{{ $u->no_hp }}" placeholder="No. HP (opsional)" class="input">
                                <label class="flex items-center gap-1.5 text-xs text-slate-600 font-semibold">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" {{ $u->is_active ? 'checked' : '' }} class="rounded">
                                    Aktif
                                </label>
                                <div class="flex gap-2 sm:col-span-3">
                                    <button type="submit" class="btn-primary h-[38px]">Simpan</button>
                                    <button type="button" @click="editing = false" class="btn-outline h-[38px]">Batal</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                </tbody>
                @empty
                <tbody>
                    <tr><td colspan="7" class="text-center text-slate-400 py-8">Belum ada data.</td></tr>
                </tbody>
                @endforelse
            </table>
        </div>
        <div class="mt-4">{{ $users->links() }}</div>
    </div>
</div>
@endsection
