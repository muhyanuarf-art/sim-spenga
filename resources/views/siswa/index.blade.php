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
            <a href="{{ route('siswa.import.form') }}" class="btn-outline"><i class="fa-solid fa-file-import mr-1.5"></i> Import Excel</a>
            <button @click="showForm = !showForm" class="btn-primary">+ Tambah Siswa</button>
        </div>
    </div>

    <div class="card p-5" x-show="showForm" x-cloak x-transition>
        <p class="font-bold text-slate-800 mb-4">Tambah Siswa</p>
        <form method="POST" action="{{ route('siswa.store') }}" class="grid sm:grid-cols-4 gap-3 items-end">
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
            <input type="text" name="nama_ortu" placeholder="Nama Orang Tua/Wali (opsional)" class="input sm:col-span-2">
            <input type="text" name="no_wa_ortu" placeholder="No. WhatsApp Ortu, mis. 081234567890" class="input">
            <button type="submit" class="btn-primary h-[38px]">Simpan</button>
        </form>
    </div>

    <div class="card p-5">
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>NIS</th><th>Nama</th><th>L/P</th><th>Kelas</th><th>WA Ortu</th><th>Status</th><th class="th-aksi">Aksi</th></tr></thead>
                @forelse($siswas as $s)
                <tbody x-data="{ editing: false, pindah: false }">
                    <tr x-show="!editing && !pindah">
                        <td>{{ $s->nis }}</td>
                        <td class="font-medium">{{ $s->nama }}</td>
                        <td>{{ $s->jenis_kelamin }}</td>
                        <td>{{ $s->kelas->nama_kelas }}</td>
                        <td>
                            @if($s->no_wa_ortu)
                                <span class="badge bg-emerald-50 text-emerald-700" title="{{ $s->nama_ortu }}"><i class="fa-solid fa-mobile-screen mr-1.5"></i> {{ $s->no_wa_ortu }}</span>
                            @else
                                <span class="badge bg-slate-100 text-slate-400">Belum diisi</span>
                            @endif
                        </td>
                        <td>
                            @if($s->is_active)<span class="badge bg-emerald-50 text-emerald-700">Aktif</span>
                            @else<span class="badge bg-slate-100 text-slate-500">Nonaktif</span>@endif
                        </td>
                        <td class="td-aksi">
                            <div class="action-buttons">
                                <a href="{{ route('siswa.riwayat-kelas', $s) }}" class="btn-chip btn-chip-cancel"><i class="fa-solid fa-clock-rotate-left mr-1.5"></i> Riwayat Kelas</a>
                                <button type="button" @click="pindah = true" class="btn-chip btn-chip-cancel"><i class="fa-solid fa-right-left mr-1.5"></i> Pindah Kelas</button>
                                <button type="button" @click="editing = true" class="btn-chip btn-chip-edit"><i class="fa-solid fa-pen mr-1.5"></i> Edit</button>
                                <form method="POST" action="{{ route('siswa.destroy', $s) }}" onsubmit="return confirm('Hapus siswa ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn-chip btn-chip-delete"><i class="fa-solid fa-trash mr-1.5"></i> Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr x-show="editing" x-cloak>
                        <td colspan="7" class="bg-brand-50/40">
                            <form method="POST" action="{{ route('siswa.update', $s) }}" class="grid sm:grid-cols-7 gap-3 items-end py-2">
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
                                <label class="flex items-center gap-1.5 text-xs text-slate-600 font-semibold">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" {{ $s->is_active ? 'checked' : '' }} class="rounded">
                                    Aktif
                                </label>
                                <input type="text" name="nama_ortu" value="{{ $s->nama_ortu }}" placeholder="Nama Orang Tua/Wali" class="input sm:col-span-2">
                                <input type="text" name="no_wa_ortu" value="{{ $s->no_wa_ortu }}" placeholder="No. WhatsApp Ortu" class="input">
                                <div class="flex gap-2 sm:col-span-4">
                                    <button type="submit" class="btn-primary h-[38px]">Simpan</button>
                                    <button type="button" @click="editing = false" class="btn-outline h-[38px]">Batal</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                    <tr x-show="pindah" x-cloak>
                        <td colspan="7" class="bg-amber-50/50">
                            <form method="POST" action="{{ route('siswa.pindah-kelas', $s) }}" class="grid sm:grid-cols-6 gap-3 items-end py-2">
                                @csrf
                                <div class="sm:col-span-6 text-xs text-slate-500">
                                    Pindahkan <span class="font-semibold text-slate-700">{{ $s->nama }}</span> dari kelas
                                    <span class="font-semibold text-slate-700">{{ $s->kelas->nama_kelas }}</span> ke kelas baru.
                                    Data absensi &amp; jurnal bulan-bulan sebelumnya tetap tersimpan di riwayat kelas lama.
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-500">Kelas Tujuan</label>
                                    <select name="kelas_tujuan_id" required class="input">
                                        <option value="">Pilih Kelas</option>
                                        @foreach($kelasList as $k)
                                            @if($k->id != $s->kelas_id)
                                                <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-500">Tanggal Efektif Pindah</label>
                                    <input type="date" name="tanggal_mutasi" value="{{ now()->toDateString() }}" class="input">
                                </div>
                                <input type="text" name="keterangan" placeholder="Keterangan (opsional), mis. alasan pindah" class="input sm:col-span-2">
                                <div class="flex gap-2 sm:col-span-2">
                                    <button type="submit" class="btn-primary h-[38px]">Pindahkan</button>
                                    <button type="button" @click="pindah = false" class="btn-outline h-[38px]">Batal</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                </tbody>
                @empty
                <tbody>
                    <tr><td colspan="7" class="text-center text-slate-400 py-8">Belum ada data siswa.</td></tr>
                </tbody>
                @endforelse
            </table>
        </div>
        <div class="mt-4">{{ $siswas->links() }}</div>
    </div>
</div>
@endsection
