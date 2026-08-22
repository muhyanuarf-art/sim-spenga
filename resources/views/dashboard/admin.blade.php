@extends('layouts.app')
@section('title', 'Dashboard Monitoring Sekolah')

@section('content')
<div class="space-y-6">

    @if(!$tahunAjaran)
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
            <i class="fa-solid fa-triangle-exclamation mr-1.5"></i> Belum ada Tahun Ajaran aktif. Silakan aktifkan di menu <b>Tahun Ajaran</b>.
        </div>
    @endif

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card color="indigo" icon="fa-user-graduate" label="Total Siswa" :value="$totalSiswa" />
        <x-stat-card color="amber" icon="fa-chalkboard-user" label="Total Guru" :value="$totalGuru" />
        <x-stat-card color="teal" icon="fa-school" label="Total Kelas" :value="$totalKelas" />
        <x-stat-card color="emerald" icon="fa-pen-to-square" label="Jurnal Hari Ini" :value="$jurnalHariIni" :suffix="'/ '.$jadwalHariIni.' jam'" />
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="card p-5 lg:col-span-1">
            <p class="font-bold text-slate-800 mb-4">Rekap Absensi Hari Ini</p>
            <div class="grid grid-cols-2 gap-3">
                @foreach([
                    ['Hadir', 'emerald', 'fa-circle-check'],
                    ['Sakit', 'amber', 'fa-thermometer'],
                    ['Izin', 'sky', 'fa-hand'],
                    ['Alfa', 'rose', 'fa-flag'],
                ] as [$status, $color, $icon])
                    <div class="rounded-xl bg-{{ $color }}-50 border border-{{ $color }}-100 p-3 text-center">
                        <p class="text-xl text-{{ $color }}-600"><i class="fa-solid {{ $icon }}"></i></p>
                        <p class="text-xl font-extrabold text-{{ $color }}-700 leading-tight mt-0.5">{{ $rekapHariIni[$status] ?? 0 }}</p>
                        <p class="text-xs font-semibold text-{{ $color }}-700/70">{{ $status }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="lg:col-span-2">
            <x-alfa-widget :data="$siswaAlfaHariIni" />
        </div>
    </div>

    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-4">Status Pengisian Absensi Per Kelas (Hari Ini)</p>
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>Kelas</th><th>Wali Kelas</th><th>Jumlah Siswa</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach($rekapPerKelas as $r)
                    <tr>
                        <td class="font-semibold">
                            <div class="flex items-center gap-2">
                                <x-initial-avatar :nama="$r['kelas']" />
                                {{ $r['kelas'] }}
                            </div>
                        </td>
                        <td class="text-slate-500">{{ $r['wali_kelas'] }}</td>
                        <td>{{ $r['jumlah_siswa'] }}</td>
                        <td>
                            @if($r['sudah_diabsen'])
                                <span class="badge bg-emerald-50 text-emerald-700"><i class="fa-solid fa-circle-check mr-1.5"></i> Sudah diabsen</span>
                            @else
                                <span class="badge bg-slate-100 text-slate-500"><i class="fa-solid fa-hourglass-half mr-1.5"></i> Belum ada data</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @include('dashboard.partials.onboarding-checklist', ['checklistOnboarding' => $checklistOnboarding])
</div>
@endsection
