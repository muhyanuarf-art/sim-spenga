@extends('layouts.app')
@section('title', 'Absensi Guru Tiap Mapel')

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
                <label class="block text-xs font-semibold text-slate-500 mb-1">Kelas</label>
                <select name="kelas_id" class="input min-w-[140px]" onchange="this.form.submit()">
                    @forelse($kelasDiampu as $k)
                        <option value="{{ $k->id }}" {{ $kelasId == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                    @empty
                        <option value="">-</option>
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
            <button type="button" onclick="cetakBagian('print-absensi-guru')" class="btn-outline">🖨️ Cetak</button>
        </form>
    </div>

    @if(!$guru)
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
            ⚠️ Belum ada data guru.
        </div>
    @elseif($mapelDiampu->isEmpty())
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
            ⚠️ {{ $guru->name }} belum diampukan mata pelajaran apapun oleh Kurikulum. Silakan atur di menu Mapping Guru Mengajar.
        </div>
    @elseif($kelasDiampu->isEmpty())
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
            ⚠️ {{ $guru->name }} belum mengajar {{ $mapelAktif->nama_mapel ?? 'mapel ini' }} di kelas manapun.
        </div>
    @else
        <div class="card p-5 print-section" id="print-absensi-guru">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="font-extrabold text-slate-800 text-lg">Absensi {{ $mapelAktif->nama_mapel ?? '-' }} - Kelas {{ $kelasAktif->nama_kelas ?? '-' }}</p>
                    <p class="text-sm text-slate-400">Guru: {{ $guru->name }} &middot; Bulan {{ \Carbon\Carbon::create()->month($bulan)->translatedFormat('F') }} {{ $tahun }}</p>
                </div>
            </div>

            <div class="overflow-x-auto -mx-5">
                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50">
                            <th class="border border-slate-200 px-2 py-2 sticky left-0 bg-slate-50">NIS</th>
                            <th class="border border-slate-200 px-2 py-2 sticky left-14 bg-slate-50 text-left min-w-[160px]">Nama Siswa</th>
                            @for($t = 1; $t <= $jumlahHari; $t++)
                                <th class="border border-slate-200 px-1 py-2 w-6">{{ $t }}</th>
                            @endfor
                            <th class="border border-slate-200 px-2 py-2 bg-amber-50">S</th>
                            <th class="border border-slate-200 px-2 py-2 bg-blue-50">I</th>
                            <th class="border border-slate-200 px-2 py-2 bg-red-50">A</th>
                            <th class="border border-slate-200 px-2 py-2 bg-slate-100">Jml</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rekap as $r)
                        <tr class="hover:bg-slate-50">
                            <td class="border border-slate-200 px-2 py-1.5 text-center sticky left-0 bg-white">{{ $r['siswa']->nis }}</td>
                            <td class="border border-slate-200 px-2 py-1.5 sticky left-14 bg-white font-medium whitespace-nowrap">{{ $r['siswa']->nama }}</td>
                            @for($t = 1; $t <= $jumlahHari; $t++)
                                @php $kode = $r['harian'][$t]; @endphp
                                <td class="border border-slate-200 text-center
                                    @if($kode === 'S') text-amber-600 font-bold
                                    @elseif($kode === 'I') text-blue-600 font-bold
                                    @elseif($kode === 'A') text-red-600 font-bold
                                    @endif">
                                    {{ $kode }}
                                </td>
                            @endfor
                            <td class="border border-slate-200 text-center font-bold bg-amber-50/50">{{ $r['sakit'] }}</td>
                            <td class="border border-slate-200 text-center font-bold bg-blue-50/50">{{ $r['izin'] }}</td>
                            <td class="border border-slate-200 text-center font-bold bg-red-50/50">{{ $r['alfa'] }}</td>
                            <td class="border border-slate-200 text-center font-bold bg-slate-100">{{ $r['jumlah'] }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="{{ $jumlahHari + 6 }}" class="text-center text-slate-400 py-8">Tidak ada data siswa di kelas ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex gap-6 mt-4 text-xs text-slate-500">
                <span><b>S</b> = Sakit</span>
                <span><b>I</b> = Izin</span>
                <span><b>A</b> = Alfa</span>
                <span>Kolom kosong = Hadir / tidak ada pertemuan mapel ini pada tanggal tsb</span>
            </div>
        </div>
    @endif
</div>
@endsection
