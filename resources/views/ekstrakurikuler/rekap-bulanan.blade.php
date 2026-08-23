@extends('layouts.app')
@section('title', 'Rekap Absensi Ekstrakurikuler')

@section('content')
<div class="space-y-6">
    <div class="card p-5 no-print">
        <form method="GET" class="flex flex-wrap items-end gap-3">
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
            <a href="{{ route('ekstrakurikuler.index') }}" class="btn-outline">&larr; Kembali</a>
            <button type="button" onclick="cetakBagian('print-rekap-ekskul')" class="btn-outline"><i class="fa-solid fa-print mr-1.5"></i> Cetak / Export PDF</button>
        </form>
    </div>

    <div class="card p-5 print-section" id="print-rekap-ekskul">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="font-extrabold text-slate-800 text-lg">Rekap Absensi Ekstrakurikuler — {{ $ekstrakurikuler->nama_ekstrakurikuler }}</p>
                <p class="text-sm text-slate-400">Bulan {{ \Carbon\Carbon::create()->month($bulan)->translatedFormat('F') }} {{ $tahun }} &middot; Pembina: {{ $ekstrakurikuler->daftarNamaPembina() }}</p>
            </div>
        </div>

        <p class="font-bold text-slate-700 text-sm mb-2">Absensi Siswa</p>
        <div class="overflow-x-auto -mx-5 mb-6">
            <table class="w-full text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="border border-slate-200 px-2 py-2 sticky left-0 bg-slate-50">NIS</th>
                        <th class="border border-slate-200 px-2 py-2 sticky left-14 bg-slate-50 text-left min-w-[160px]">Nama Siswa</th>
                        <th class="border border-slate-200 px-2 py-2 text-left min-w-[80px]">Kelas</th>
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
                        <td class="border border-slate-200 px-2 py-1.5 text-center sticky left-0 bg-white">{{ $r['nis'] }}</td>
                        <td class="border border-slate-200 px-2 py-1.5 sticky left-14 bg-white font-medium whitespace-nowrap">{{ $r['nama'] }}</td>
                        <td class="border border-slate-200 px-2 py-1.5 whitespace-nowrap">{{ $r['kelas'] }}</td>
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
                    <tr><td colspan="{{ $jumlahHari + 7 }}" class="text-center text-slate-400 py-8">Tidak ada data siswa untuk kegiatan ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p class="font-bold text-slate-700 text-sm mb-2">Absensi Pembina</p>
        <div class="overflow-x-auto -mx-5">
            <table class="w-full text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="border border-slate-200 px-2 py-2 sticky left-0 bg-slate-50 text-left min-w-[160px]">Nama Pembina</th>
                        <th class="border border-slate-200 px-2 py-2 sticky left-[160px] bg-slate-50">Jenis</th>
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
                    @forelse($rekapPembina as $r)
                    <tr class="hover:bg-slate-50">
                        <td class="border border-slate-200 px-2 py-1.5 sticky left-0 bg-white font-medium whitespace-nowrap">{{ $r['nama'] }}</td>
                        <td class="border border-slate-200 px-2 py-1.5 text-center sticky left-[160px] bg-white whitespace-nowrap">
                            @if($r['jenis'] === 'Luar Sekolah')
                                <span class="px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700">Luar Sekolah</span>
                            @else
                                <span class="px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-brand-50 text-brand-700">Sekolah</span>
                            @endif
                        </td>
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
                    <tr><td colspan="{{ $jumlahHari + 6 }}" class="text-center text-slate-400 py-8">Tidak ada data pembina untuk kegiatan ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex gap-6 mt-4 text-xs text-slate-500 flex-wrap">
            <span><b>S</b> = Sakit</span>
            <span><b>I</b> = Izin</span>
            <span><b>A</b> = Alfa</span>
            <span>Kolom kosong = Hadir</span>
        </div>
    </div>
</div>
@endsection
