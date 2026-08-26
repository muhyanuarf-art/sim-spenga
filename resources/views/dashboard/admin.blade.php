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
        <div class="card p-5">
            <div class="flex items-start justify-between mb-2">
                <div class="w-10 h-10 rounded-xl bg-indigo-500 text-white flex items-center justify-center shrink-0"><i class="fa-solid fa-user-graduate"></i></div>
            </div>
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wide">Total Siswa</p>
            <p class="text-2xl font-extrabold text-slate-800">{{ $totalSiswa }}</p>
            <p class="text-xs text-slate-400 mt-0.5">Laki-laki: {{ $totalSiswaLaki }} &middot; Perempuan: {{ $totalSiswaPerempuan }}</p>
            <a href="{{ route('siswa.index') }}" class="text-xs text-brand-600 font-semibold mt-2 inline-block">Lihat Detail &rarr;</a>
        </div>
        <div class="card p-5">
            <div class="flex items-start justify-between mb-2">
                <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0"><i class="fa-solid fa-chalkboard-user"></i></div>
            </div>
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wide">Total Guru</p>
            <p class="text-2xl font-extrabold text-slate-800">{{ $totalGuru }}</p>
            <p class="text-xs text-slate-400 mt-0.5">Aktif: {{ $totalGuruAktif }} &middot; Tidak Aktif: {{ $totalGuruTidakAktif }}</p>
            <a href="{{ route('users.index') }}" class="text-xs text-brand-600 font-semibold mt-2 inline-block">Lihat Detail &rarr;</a>
        </div>
        <div class="card p-5">
            <div class="flex items-start justify-between mb-2">
                <div class="w-10 h-10 rounded-xl bg-teal-500 text-white flex items-center justify-center shrink-0"><i class="fa-solid fa-school"></i></div>
            </div>
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wide">Total Kelas</p>
            <p class="text-2xl font-extrabold text-slate-800">{{ $totalKelas }}</p>
            <p class="text-xs text-slate-400 mt-0.5">Rombel Aktif: {{ $totalKelas }} &middot; Tidak Aktif: {{ $totalKelasTidakAktif }}</p>
            <a href="{{ route('kelas.index') }}" class="text-xs text-brand-600 font-semibold mt-2 inline-block">Lihat Detail &rarr;</a>
        </div>
        <div class="card p-5">
            <div class="flex items-start justify-between mb-2">
                <div class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0"><i class="fa-solid fa-pen-to-square"></i></div>
            </div>
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wide">Jurnal Hari Ini</p>
            <p class="text-2xl font-extrabold text-slate-800">{{ $jurnalHariIni }} <span class="text-sm font-semibold text-slate-400">/ {{ $jadwalHariIni }} sesi</span></p>
            <p class="text-xs text-slate-400 mt-0.5">Terselesaikan dari total sesi mengajar hari ini</p>
            <a href="{{ route('rekap.index') }}" class="text-xs text-brand-600 font-semibold mt-2 inline-block">Lihat Detail &rarr;</a>
        </div>
    </div>

    {{-- Rekap Absensi Hari Ini + Siswa Alfa Hari Ini — DITUMPUK (bukan
         berdampingan), sesuai permintaan: Siswa Alfa persis di bawah
         Rekap Absensi. --}}
    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-4">Rekap Absensi Hari Ini</p>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
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

    <x-alfa-widget :data="$siswaAlfaHariIni" />

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="card p-5">
            <p class="font-bold text-slate-800 mb-4">Status Pengisian Absensi Per Kelas (Hari Ini)</p>
            <div class="overflow-x-auto -mx-5">
                <table class="table-clean w-full">
                    <thead><tr><th>Kelas</th><th>Wali Kelas</th><th>Siswa</th><th>Terisi</th><th>Persentase</th><th>Status</th></tr></thead>
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
                            <td>{{ $r['terisi'] }}</td>
                            <td class="min-w-[100px]">
                                <div class="flex items-center gap-2">
                                    <div class="w-16 h-1.5 rounded-full bg-slate-200 overflow-hidden">
                                        <div class="h-full bg-brand-500" style="width: {{ $r['persentase'] }}%"></div>
                                    </div>
                                    <span class="text-xs text-slate-500">{{ $r['persentase'] }}%</span>
                                </div>
                            </td>
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

        {{-- Grafik Kehadiran 7 Hari Terakhir — SVG polos, tanpa library JS. --}}
        <div class="card p-5">
            <p class="font-bold text-slate-800 mb-4">Grafik Kehadiran 7 Hari Terakhir</p>
            <div class="flex items-center gap-3 mb-3 text-xs flex-wrap">
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Hadir</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Sakit</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-sky-500"></span> Izin</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Alfa</span>
            </div>
            @php
                $maxNilai = max(1, $statistikMingguan->flatMap(fn ($b) => [$b['Hadir'], $b['Sakit'], $b['Izin'], $b['Alfa']])->max());
                $lebar = 600; $tinggi = 160; $n = $statistikMingguan->count();
                $jarakX = $n > 1 ? $lebar / ($n - 1) : $lebar;
                $titik = function (string $kunci) use ($statistikMingguan, $maxNilai, $tinggi, $jarakX) {
                    return $statistikMingguan->values()->map(fn ($b, $i) => round($i * $jarakX, 1) . ',' . round($tinggi - ($b[$kunci] / $maxNilai) * $tinggi, 1))->implode(' ');
                };
            @endphp
            <svg viewBox="0 0 {{ $lebar }} {{ $tinggi + 20 }}" class="w-full" preserveAspectRatio="none" style="height:180px">
                <polyline points="{{ $titik('Hadir') }}" fill="none" stroke="#10b981" stroke-width="2.5" />
                <polyline points="{{ $titik('Sakit') }}" fill="none" stroke="#f59e0b" stroke-width="2.5" />
                <polyline points="{{ $titik('Izin') }}" fill="none" stroke="#0ea5e9" stroke-width="2.5" />
                <polyline points="{{ $titik('Alfa') }}" fill="none" stroke="#f43f5e" stroke-width="2.5" />
            </svg>
            <div class="flex justify-between text-xs text-slate-400 mt-1">
                @foreach($statistikMingguan as $b)<span>{{ $b['label'] }}</span>@endforeach
            </div>
        </div>
    </div>

    @include('dashboard.partials.onboarding-checklist', ['checklistOnboarding' => $checklistOnboarding])

    {{-- Aktivitas Terbaru — lihat catatan penting di DashboardController:
         BUKAN audit log sungguhan (tidak tahu siapa pelakunya), cuma
         gabungan record terbaru dari beberapa tabel yang sudah ada. --}}
    <div class="card p-5">
        <div class="flex items-center justify-between mb-3">
            <p class="font-bold text-slate-800">Aktivitas Terbaru</p>
            <span class="text-xs text-slate-400" title="Bukan audit log — cuma record terbaru dari beberapa tabel">
                <i class="fa-solid fa-circle-info"></i>
            </span>
        </div>
        <div class="space-y-3">
            @forelse($aktivitasTerbaru as $a)
                <div class="flex items-start justify-between gap-3 text-sm">
                    <div class="flex items-start gap-2.5 min-w-0">
                        <div class="w-1.5 h-1.5 rounded-full bg-brand-500 mt-1.5 shrink-0"></div>
                        <div class="min-w-0">
                            <p class="text-slate-700 truncate">{{ $a['teks'] }}</p>
                            <p class="text-xs text-slate-400">{{ $a['waktu']?->diffForHumans() }}</p>
                        </div>
                    </div>
                    <span class="badge bg-slate-100 text-slate-500 shrink-0">{{ $a['tag'] }}</span>
                </div>
            @empty
                <p class="text-xs text-slate-400">Belum ada aktivitas tercatat.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
