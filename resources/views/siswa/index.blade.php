@extends('layouts.app')
@section('title', 'Data Siswa')

@section('content')
<div class="space-y-6" x-data="{ showForm: false }">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <form method="GET" class="flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama/NIS..." class="input max-w-xs">
            <select name="kelas_id" class="input max-w-[160px]" onchange="this.form.submit()">
                <option value="">Semua Kelas</option>
                @foreach($kelasList as $k)<option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>@endforeach
            </select>
            <button class="btn-outline">Cari</button>
        </form>
        <div class="flex gap-2">
            <a href="{{ route('siswa.import.form') }}" class="btn-outline">📥 Import Excel</a>
            <button @click="showForm = !showForm" class="btn-primary">+ Tambah Siswa</button>
        </div>
    </div>

    <div class="card p-5" x-show="showForm" x-cloak x-transition>
        <p class="font-bold text-slate-800 mb-4">Tambah Siswa</p>
        <form method="POST" action="{{ route('siswa.store') }}" class="grid sm:grid-cols-6 gap-3 items-end">
            @csrf
            <input type="text" name="nis" placeholder="NIS" required class="input">
            <input type="text" name="nisn" placeholder="NISN (opsional)" class="input">
            <input type="text" name="nama" placeholder="Nama Lengkap" required class="input sm:col-span-2">
            <select name="jenis_kelamin" required class="input">
                <option value="L">Laki-laki</option>
                <option value="P">Perempuan</option>
            </select>
            <select name="kelas_id" required class="input">
                <option value="">Pilih Kelas</option>
                @foreach($kelasList as $k)<option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>@endforeach
            </select>
            <input type="text" name="no_hp_ortu" placeholder="No. WA Orang Tua (62...)" class="input sm:col-span-2">
            <button type="submit" class="btn-primary h-[38px]">Simpan</button>
        </form>
    </div>

    <div class="card p-5">
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>NIS</th><th>Nama</th><th>L/P</th><th>Kelas</th><th>Status</th><th class="th-aksi">Aksi</th></tr></thead>
                @forelse($siswas as $s)
                <tbody x-data="{ editing: false }">
                    <tr x-show="!editing">
                        <td>{{ $s->nis }}</td>
                        <td class="font-medium">{{ $s->nama }}</td>
                        <td>{{ $s->jenis_kelamin }}</td>
                        <td>{{ $s->kelas->nama_kelas }}</td>
                        <td>
                            @if($s->is_active)<span class="badge bg-emerald-50 text-emerald-700">Aktif</span>
                            @else<span class="badge bg-slate-100 text-slate-500">Nonaktif</span>@endif
                        </td>
                        <td class="td-aksi">
                            <div class="action-buttons">
                                <button type="button" @click="editing = true" class="btn-chip btn-chip-edit">✏️ Edit</button>
                                <form method="POST" action="{{ route('siswa.destroy', $s) }}" onsubmit="return confirm('Hapus siswa ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn-chip btn-chip-delete">🗑️ Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr x-show="editing" x-cloak>
                        <td colspan="6" class="bg-brand-50/40">
                            <form method="POST" action="{{ route('siswa.update', $s) }}" class="grid sm:grid-cols-8 gap-3 items-end py-2">
                                @csrf @method('PUT')
                                <input type="text" name="nis" value="{{ $s->nis }}" placeholder="NIS" required class="input">
                                <input type="text" name="nisn" value="{{ $s->nisn }}" placeholder="NISN" class="input">
                                <input type="text" name="nama" value="{{ $s->nama }}" placeholder="Nama Lengkap" required class="input sm:col-span-2">
                                <select name="jenis_kelamin" required class="input">
                                    <option value="L" {{ $s->jenis_kelamin === 'L' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="P" {{ $s->jenis_kelamin === 'P' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                                <select name="kelas_id" required class="input">
                                    @foreach($kelasList as $k)
                                        <option value="{{ $k->id }}" {{ $s->kelas_id == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="no_hp_ortu" value="{{ $s->no_hp_ortu }}" placeholder="No. WA Orang Tua (62...)" class="input">
                                <label class="flex items-center gap-1.5 text-xs text-slate-600 font-semibold">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" {{ $s->is_active ? 'checked' : '' }} class="rounded">
                                    Aktif
                                </label>
                                <div class="flex gap-2 sm:col-span-8">
                                    <button type="submit" class="btn-primary h-[38px]">Simpan</button>
                                    <button type="button" @click="editing = false" class="btn-outline h-[38px]">Batal</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                </tbody>
                @empty
                <tbody>
                    <tr><td colspan="6" class="text-center text-slate-400 py-8">Belum ada data siswa.</td></tr>
                </tbody>
                @endforelse
            </table>
        </div>
        <div class="mt-4">{{ $siswas->links() }}</div>
    </div>
</div>
@endsection
