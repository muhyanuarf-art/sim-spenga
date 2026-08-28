@extends('layouts.app')
@section('title', 'Data Kelas')

@section('content')
<div class="space-y-6" x-data="{ showForm: false, showSalin: false }">

    {{-- STEP 5 Bagian 4/23 — pemilih Tahun Ajaran (default: aktif) --}}
    <div class="card p-5">
        <form method="GET" action="{{ route('kelas.index') }}" class="flex flex-wrap gap-3 items-end">
            <div class="min-w-[220px]">
                <label class="block text-xs font-semibold text-slate-500 mb-1">Tahun Ajaran</label>
                <select name="tahun_ajaran_id" class="input" onchange="this.form.submit()">
                    @forelse($tahunAjaranList as $t)
                        <option value="{{ $t->id }}" {{ $tahunAjaranDipilih && $tahunAjaranDipilih->id === $t->id ? 'selected' : '' }}>
                            {{ $t->nama }}{{ $t->is_active ? ' (Aktif)' : '' }}
                        </option>
                    @empty
                        <option value="">Belum ada Tahun Ajaran</option>
                    @endforelse
                </select>
            </div>
            <noscript><button class="btn-outline">Tampilkan</button></noscript>
        </form>
    </div>

    @if($tahunAjaranDipilih)
    <div class="flex justify-end gap-2">
        <a href="{{ route('kelas.import.form') }}" class="btn-outline"><i class="fa-solid fa-file-import mr-1.5"></i> Import Excel</a>
        @if($tahunAjaranSumberPilihan->isNotEmpty())
        <button @click="showSalin = !showSalin" class="btn-outline"><i class="fa-solid fa-clipboard-list mr-1.5"></i> Salin Struktur Kelas</button>
        @endif
        <button @click="showForm = !showForm" class="btn-primary">+ Tambah Kelas</button>
    </div>

    {{-- STEP 5 Bagian 14 — Salin Struktur Kelas dari Tahun Ajaran lain --}}
    @if($tahunAjaranSumberPilihan->isNotEmpty())
    <div class="card p-5" x-show="showSalin" x-cloak x-transition>
        <p class="font-bold text-slate-800 mb-1">Salin Struktur Kelas</p>
        <p class="text-sm text-slate-500 mb-4">
            Menyalin nama kelas & tingkat dari tahun ajaran lain ke <span class="font-semibold">{{ $tahunAjaranDipilih->nama }}</span>.
            Wali Kelas TIDAK ikut disalin (atur ulang manual di bawah). Hasil salinan menjadi kelas BARU
            (ID baru) — kelas yang nama & tingkatnya sudah ada di {{ $tahunAjaranDipilih->nama }} otomatis dilewati.
        </p>
        <form method="POST" action="{{ route('kelas.salin') }}" class="flex flex-wrap gap-3 items-end">
            @csrf
            <input type="hidden" name="tahun_ajaran_tujuan_id" value="{{ $tahunAjaranDipilih->id }}">
            <div class="min-w-[220px]">
                <label class="block text-xs font-semibold text-slate-500 mb-1">Salin dari Tahun Ajaran</label>
                <select name="tahun_ajaran_sumber_id" required class="input">
                    <option value="">Pilih sumber...</option>
                    @foreach($tahunAjaranSumberPilihan as $t)
                        <option value="{{ $t->id }}">{{ $t->nama }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-primary h-[38px]">Salin ke {{ $tahunAjaranDipilih->nama }}</button>
        </form>
    </div>
    @endif

    <div class="card p-5" x-show="showForm" x-cloak x-transition>
        <p class="font-bold text-slate-800 mb-4">Tambah Kelas — Tahun Ajaran {{ $tahunAjaranDipilih->nama }}</p>
        <form method="POST" action="{{ route('kelas.store') }}" class="grid sm:grid-cols-4 gap-3 items-end">
            @csrf
            <input type="hidden" name="tahun_ajaran_id" value="{{ $tahunAjaranDipilih->id }}">
            <input type="text" name="nama_kelas" placeholder="Contoh: 7A" required class="input">
            <select name="tingkat" required class="input">
                <option value="7">Tingkat 7</option>
                <option value="8">Tingkat 8</option>
                <option value="9">Tingkat 9</option>
            </select>
            <select name="wali_kelas_id" class="input">
                <option value="">Wali Kelas (opsional)</option>
                @foreach($guruList as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach
            </select>
            <button type="submit" class="btn-primary h-[38px]">Simpan</button>
        </form>
    </div>

    <div class="card p-5">
        <p class="text-xs text-slate-400 mb-2">
            Kelas di halaman ini KHUSUS Tahun Ajaran {{ $tahunAjaranDipilih->nama }}. Nama kelas yang sama
            (mis. "7A") di tahun ajaran lain adalah baris/data yang BERBEDA — mengubah Wali Kelas di sini
            tidak memengaruhi tahun ajaran lain sama sekali.
        </p>
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th class="w-12 text-center">No</th><th>Kelas</th><th>Tingkat</th><th>Wali Kelas</th><th>Jumlah Siswa</th><th class="th-aksi">Aksi</th></tr></thead>
                @forelse($kelas as $k)
                <tbody x-data="{ editing: false }">
                    <tr x-show="!editing">
                        <td class="text-center text-slate-400">{{ $kelas->firstItem() + $loop->index }}</td>
                        <td class="font-semibold">{{ $k->nama_kelas }}</td>
                        <td>{{ $k->tingkat }}</td>
                        <td>{{ $k->waliKelas->name ?? '-' }}</td>
                        <td>{{ $k->siswas_count }}</td>
                        <td class="td-aksi">
                            <div class="action-buttons">
                                <button type="button" @click="editing = true" class="btn-chip btn-chip-edit"><i class="fa-solid fa-pen mr-1.5"></i> Edit</button>
                                <form method="POST" action="{{ route('kelas.destroy', $k) }}" onsubmit="return confirm('Hapus kelas ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn-chip btn-chip-delete"><i class="fa-solid fa-trash mr-1.5"></i> Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr x-show="editing" x-cloak>
                        <td colspan="6" class="bg-brand-50/40">
                            <form method="POST" action="{{ route('kelas.update', $k) }}" class="grid sm:grid-cols-5 gap-3 items-end py-2">
                                @csrf @method('PUT')
                                <input type="text" name="nama_kelas" value="{{ $k->nama_kelas }}" required class="input">
                                <select name="tingkat" required class="input">
                                    @foreach([7,8,9] as $t)
                                        <option value="{{ $t }}" {{ $k->tingkat == $t ? 'selected' : '' }}>Tingkat {{ $t }}</option>
                                    @endforeach
                                </select>
                                <select name="wali_kelas_id" class="input">
                                    <option value="">Wali Kelas (opsional)</option>
                                    @foreach($guruList as $g)
                                        <option value="{{ $g->id }}" {{ optional($k->waliKelas)->id == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn-primary h-[38px]">Simpan</button>
                                <button type="button" @click="editing = false" class="btn-outline h-[38px]">Batal</button>
                                {{-- Wali kelas adalah penugasan PER SEMESTER sejak 2026-08-28
                                     (lihat App\Models\PenugasanWaliKelas) — perlu dijelaskan di
                                     sini supaya admin tidak mengira semester lain ikut berubah. --}}
                                @if($tahunAjaranDipilih && \App\Models\TahunAjaran::aktif()?->nama === $tahunAjaranDipilih->nama)
                                <p class="sm:col-span-5 -mt-1 text-xs text-slate-500">
                                    <i class="fa-solid fa-circle-info mr-1"></i>
                                    Perubahan wali kelas hanya berlaku untuk <strong>Semester {{ \App\Models\TahunAjaran::aktif()->semester }}</strong>.
                                    Semester lain pada tahun ajaran ini tidak ikut berubah — jadi pergantian wali kelas
                                    di tengah tahun tidak merusak laporan semester yang sudah lewat.
                                </p>
                                @else
                                <p class="sm:col-span-5 -mt-1 text-xs text-slate-500">
                                    <i class="fa-solid fa-circle-info mr-1"></i>
                                    Tahun ajaran ini belum berjalan, jadi wali kelas ditetapkan untuk
                                    <strong>Semester Ganjil &amp; Genap sekaligus</strong>. Nanti bisa diubah per semester
                                    setelah tahun ajarannya aktif.
                                </p>
                                @endif
                            </form>
                        </td>
                    </tr>
                </tbody>
                @empty
                <tbody>
                    <tr><td colspan="6" class="text-center text-slate-400 py-8">Belum ada kelas untuk Tahun Ajaran {{ $tahunAjaranDipilih->nama }}.</td></tr>
                </tbody>
                @endforelse
            </table>
        </div>
        <div class="mt-4">{{ $kelas->links() }}</div>
    </div>
    @else
    <div class="card p-5">
        <p class="text-sm text-amber-600">Belum ada Tahun Ajaran. Buat dulu di menu Tahun Ajaran.</p>
    </div>
    @endif
</div>
@endsection
