@extends('layouts.app')
@section('title', 'Dashboard Monitoring Sekolah')

@section('content')
<div class="space-y-6">

    @if(!$tahunAjaran)
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
            ⚠️ Belum ada Tahun Ajaran aktif. Silakan aktifkan di menu <b>Tahun Ajaran</b>.
        </div>
    @endif

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Total Siswa</p>
            <p class="text-2xl font-extrabold text-slate-800">{{ $totalSiswa }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Total Guru</p>
            <p class="text-2xl font-extrabold text-slate-800">{{ $totalGuru }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Total Kelas</p>
            <p class="text-2xl font-extrabold text-slate-800">{{ $totalKelas }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Jurnal Hari Ini</p>
            <p class="text-2xl font-extrabold text-slate-800">{{ $jurnalHariIni }} <span class="text-sm text-slate-400 font-medium">/ {{ $jadwalHariIni }} jam pelajaran</span></p>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="card p-5 lg:col-span-1">
            <p class="font-bold text-slate-800 mb-4">Rekap Absensi Hari Ini</p>
            <div class="space-y-3">
                @foreach(['Hadir' => 'emerald', 'Sakit' => 'amber', 'Izin' => 'blue', 'Alfa' => 'red'] as $status => $color)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-600">{{ $status }}</span>
                        <span class="badge bg-{{ $color }}-50 text-{{ $color }}-700">{{ $rekapHariIni[$status] ?? 0 }} siswa</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card p-5 lg:col-span-2">
            <p class="font-bold text-slate-800 mb-4">Status Pengisian Absensi Per Kelas (Hari Ini)</p>
            <div class="overflow-x-auto -mx-5">
                <table class="table-clean w-full">
                    <thead><tr><th>Kelas</th><th>Wali Kelas</th><th>Jumlah Siswa</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach($rekapPerKelas as $r)
                        <tr>
                            <td class="font-semibold">{{ $r['kelas'] }}</td>
                            <td>{{ $r['wali_kelas'] }}</td>
                            <td>{{ $r['jumlah_siswa'] }}</td>
                            <td>
                                @if($r['sudah_diabsen'])
                                    <span class="badge bg-emerald-50 text-emerald-700">Sudah diabsen</span>
                                @else
                                    <span class="badge bg-slate-100 text-slate-500">Belum ada data</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
