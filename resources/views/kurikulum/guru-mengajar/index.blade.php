@extends('layouts.app')
@section('title', 'Pemetaan Guru Mengajar')

@section('content')
<div class="space-y-6" x-data="{ showForm: false }">
    @php
        // STEP 6 Bagian 19/20 — tombol tambah/edit/hapus HANYA muncul kalau
        // yang sedang DILIHAT adalah periode AKTIF (karena store()/update()
        // selalu menulis ke periode aktif) DAN periode itu belum terkunci.
        // Melihat periode lain (histori) SELALU read-only di halaman ini,
        // apa pun status kuncinya.
        $bisaEdit = $periodeDilihat && $periodeAktif && $periodeDilihat->id === $periodeAktif->id && ! $periodeDilihat->isTerkunci();
    @endphp

    {{-- STEP 6 Bagian 19/20 — pemilih periode (default aktif, bisa lihat histori) --}}
    <div class="card p-5">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="min-w-[220px]">
                <label class="block text-xs font-semibold text-slate-500 mb-1">Periode</label>
                <select name="tahun_ajaran_id" class="input" onchange="this.form.submit()">
                    @foreach($tahunAjaranList as $t)
                        <option value="{{ $t->id }}" {{ $periodeDilihat && $periodeDilihat->id === $t->id ? 'selected' : '' }}>
                            {{ $t->labelPeriode() }}{{ $periodeAktif && $periodeAktif->id === $t->id ? ' (Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    @if(!$periodeAktif)
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
            <i class="fa-solid fa-triangle-exclamation mr-1.5"></i> Aktifkan Tahun Ajaran terlebih dahulu sebelum menambah mapping.
        </div>
    @elseif(!$periodeDilihat)
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
            <i class="fa-solid fa-triangle-exclamation mr-1.5"></i> Periode tidak ditemukan.
        </div>
    @elseif($periodeDilihat->id !== $periodeAktif->id)
        <div class="rounded-xl bg-slate-100 border border-slate-200 text-slate-600 px-4 py-3 text-sm">
            <i class="fa-solid fa-book-open mr-1.5"></i> Anda sedang melihat histori periode {{ $periodeDilihat->labelPeriode() }} (bukan periode aktif). Data ini hanya dapat dilihat, tidak dapat ditambah/diubah/dihapus dari sini.
        </div>
    @elseif($periodeDilihat->isTerkunci())
        <div class="rounded-xl bg-slate-100 border border-slate-200 text-slate-600 px-4 py-3 text-sm">
            <i class="fa-solid fa-lock mr-1.5"></i> Periode {{ $periodeDilihat->labelPeriode() }} sudah ditutup dan terkunci. Data pada periode ini hanya dapat dilihat, tidak dapat ditambah/diubah/dihapus.
        </div>
    @endif

    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex gap-2">
            <a href="{{ route('kurikulum.guru-mengajar.import.form') }}" class="btn-outline"><i class="fa-solid fa-file-import mr-1.5"></i> Import Excel</a>
        </div>
        @if($bisaEdit)
        <button @click="showForm = !showForm" class="btn-primary">+ Tambah Mapping</button>
        @endif
    </div>

    <div class="card p-5" x-show="showForm" x-cloak x-transition>
        <p class="font-bold text-slate-800 mb-4">Tambah Mapping Guru Mengajar</p>
        <form method="POST" action="{{ route('kurikulum.guru-mengajar.store') }}" class="grid sm:grid-cols-4 gap-3 items-end">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Guru</label>
                <select name="guru_id" required class="input">
                    <option value="">Pilih Guru</option>
                    @foreach($guruList as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Kelas</label>
                <select name="kelas_id" required class="input">
                    <option value="">Pilih Kelas</option>
                    @foreach($kelasList as $k)<option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Mata Pelajaran</label>
                <select name="mata_pelajaran_id" required class="input">
                    <option value="">Pilih Mapel</option>
                    @foreach($mapelList as $m)<option value="{{ $m->id }}">{{ $m->nama_mapel }}</option>@endforeach
                </select>
            </div>
            <button type="submit" class="btn-primary h-[38px]">Simpan</button>
        </form>
    </div>

    <div class="card p-5">
        <form method="GET" class="flex flex-wrap gap-3 mb-4">
            <input type="hidden" name="tahun_ajaran_id" value="{{ $periodeDilihat->id ?? '' }}">
            <select name="kelas_id" class="input max-w-[180px]" onchange="this.form.submit()">
                <option value="">Semua Kelas</option>
                @foreach($kelasList as $k)<option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>@endforeach
            </select>
            <select name="guru_id" class="input max-w-[220px]" onchange="this.form.submit()">
                <option value="">Semua Guru</option>
                @foreach($guruList as $g)<option value="{{ $g->id }}" {{ request('guru_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>@endforeach
            </select>
        </form>

        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>Kelas</th><th>Mata Pelajaran</th><th>Guru</th><th class="th-aksi">Aksi</th></tr></thead>
                @forelse($data as $d)
                <tbody x-data="{ editing: false }">
                    <tr x-show="!editing">
                        <td class="font-semibold">{{ $d->kelas->nama_kelas }}</td>
                        <td>{{ $d->mapel->nama_mapel }}</td>
                        <td>{{ $d->guru->name }}</td>
                        <td class="td-aksi">
                            <div class="action-buttons">
                                @if(!$bisaEdit)
                                    <span class="text-xs text-slate-400"><i class="fa-solid fa-lock mr-1.5"></i> Tidak dapat diubah</span>
                                @else
                                <button type="button" @click="editing = true" class="btn-chip btn-chip-edit"><i class="fa-solid fa-pen mr-1.5"></i> Edit</button>
                                <form method="POST" action="{{ route('kurikulum.guru-mengajar.destroy', $d) }}" onsubmit="return confirm('Hapus mapping ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn-chip btn-chip-delete"><i class="fa-solid fa-trash mr-1.5"></i> Hapus</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    <tr x-show="editing" x-cloak>
                        <td colspan="4" class="bg-brand-50/40">
                            <form method="POST" action="{{ route('kurikulum.guru-mengajar.update', $d) }}" class="grid sm:grid-cols-4 gap-3 items-end py-2">
                                @csrf @method('PUT')
                                <select name="kelas_id" required class="input">
                                    @foreach($kelasList as $k)
                                        <option value="{{ $k->id }}" {{ $d->kelas_id == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                                    @endforeach
                                </select>
                                <select name="mata_pelajaran_id" required class="input">
                                    @foreach($mapelList as $m)
                                        <option value="{{ $m->id }}" {{ $d->mata_pelajaran_id == $m->id ? 'selected' : '' }}>{{ $m->nama_mapel }}</option>
                                    @endforeach
                                </select>
                                <select name="guru_id" required class="input">
                                    @foreach($guruList as $g)
                                        <option value="{{ $g->id }}" {{ $d->guru_id == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                                    @endforeach
                                </select>
                                <div class="flex gap-2">
                                    <button type="submit" class="btn-primary h-[38px]">Simpan</button>
                                    <button type="button" @click="editing = false" class="btn-outline h-[38px]">Batal</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                </tbody>
                @empty
                <tbody>
                    <tr><td colspan="4" class="text-center text-slate-400 py-8">Belum ada data mapping.</td></tr>
                </tbody>
                @endforelse
            </table>
        </div>
        <div class="mt-4">{{ $data->links() }}</div>
    </div>
</div>
@endsection
