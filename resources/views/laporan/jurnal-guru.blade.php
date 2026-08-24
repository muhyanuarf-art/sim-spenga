@extends('layouts.app')
@section('title', 'Jurnal Mengajar Guru Tiap Mapel')

@section('content')
<div class="space-y-6">

    <div class="card p-5 no-print">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            @if(!$isGuru)
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Guru</label>
                <select name="guru_id" class="input min-w-[200px]" onchange="this.form.submit()">
                    @foreach($guruList as $g)
                        <option value="{{ $g->id }}" {{ $guru && $guru->id === $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Guru</label>
                <div class="input bg-slate-50 text-slate-500 font-medium">{{ $guru->name }}</div>
            </div>
            @endif

            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Mata Pelajaran</label>
                <select name="mapel_id" class="input min-w-[200px]" onchange="this.form.submit()">
                    @forelse($mapelDiampu as $m)
                        <option value="{{ $m->id }}" {{ $mapelId == $m->id ? 'selected' : '' }}>{{ $m->nama_mapel }}</option>
                    @empty
                        <option value="">Guru belum mengampu mapel apapun</option>
                    @endforelse
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Bulan</label>
                <select name="bulan" class="input" onchange="this.form.submit()">
                    @foreach(range(1,12) as $b)
                        <option value="{{ $b }}" {{ $b === $bulan ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($b)->translatedFormat('F') }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Tahun</label>
                <select name="tahun" class="input" onchange="this.form.submit()">
                    @foreach(range(now()->year - 1, now()->year + 1) as $y)
                        <option value="{{ $y }}" {{ $y === $tahun ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" onclick="cetakBagian('print-jurnal-guru')" class="btn-outline"><i class="fa-solid fa-print mr-1.5"></i> Cetak</button>
        </form>
    </div>

    @if(!$guru)
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
            <i class="fa-solid fa-triangle-exclamation mr-1.5"></i> Belum ada data guru.
        </div>
    @elseif($mapelDiampu->isEmpty())
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
            <i class="fa-solid fa-triangle-exclamation mr-1.5"></i> {{ $guru->name }} belum diampukan mata pelajaran apapun oleh Kurikulum. Silakan atur di menu Mapping Guru Mengajar.
        </div>
    @else
        <div class="print-section" id="print-jurnal-guru">
        <x-kop-surat />

        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="card p-5">
                <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Jumlah Pertemuan</p>
                <p class="text-2xl font-extrabold text-slate-800">{{ $ringkasan['pertemuan'] }}</p>
            </div>
            <div class="card p-5">
                <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Total Hadir</p>
                <p class="text-2xl font-extrabold text-emerald-600">{{ $ringkasan['hadir'] }}</p>
            </div>
            <div class="card p-5">
                <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Total Sakit</p>
                <p class="text-2xl font-extrabold text-amber-600">{{ $ringkasan['sakit'] }}</p>
            </div>
            <div class="card p-5">
                <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Total Izin</p>
                <p class="text-2xl font-extrabold text-blue-600">{{ $ringkasan['izin'] }}</p>
            </div>
            <div class="card p-5">
                <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Total Alfa</p>
                <p class="text-2xl font-extrabold text-red-600">{{ $ringkasan['alfa'] }}</p>
            </div>
        </div>

        <div class="card p-5">
            <p class="font-extrabold text-slate-800 text-lg mb-1">Jurnal Mengajar - {{ $guru->name }}</p>
            <p class="text-sm text-slate-400 mb-4">
                Mata Pelajaran: <b>{{ $mapelAktif->nama_mapel ?? '-' }}</b>
                &middot; {{ \Carbon\Carbon::create()->month($bulan)->translatedFormat('F') }} {{ $tahun }}
            </p>

            <div class="overflow-x-auto -mx-5">
                <table class="table-clean w-full">
                    <thead>
                        <tr><th class="w-10">No</th><th>Tanggal</th><th>Jam</th><th>Kelas</th><th>Materi</th><th>Kegiatan</th><th>H/S/I/A</th></tr>
                    </thead>
                    <tbody>
                        @forelse($jurnal as $j)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="whitespace-nowrap">{{ $j->tanggal->translatedFormat('d M Y') }}</td>
                            <td class="whitespace-nowrap">{{ $j->label_sesi }}</td>
                            <td class="font-semibold">{{ $j->kelas->nama_kelas }}</td>
                            <td class="text-slate-600">{{ $j->materi }}</td>
                            <td class="text-slate-500">{{ $j->kegiatan ?: '-' }}</td>
                            <td class="whitespace-nowrap text-xs">
                                <span class="text-emerald-600 font-bold">{{ $j->jumlah_hadir }}</span> /
                                <span class="text-amber-600 font-bold">{{ $j->jumlah_sakit }}</span> /
                                <span class="text-blue-600 font-bold">{{ $j->jumlah_izin }}</span> /
                                <span class="text-red-600 font-bold">{{ $j->jumlah_alfa }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-slate-400 py-8">Belum ada jurnal pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-blok-tanda-tangan
                jabatan="Guru {{ $mapelAktif->nama_mapel ?? '' }}"
                :nama="$guru->name ?? null"
                :nip="$guru->nip ?? null"
            />
        </div>
        </div>
    @endif
</div>
@endsection
