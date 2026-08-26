@extends('layouts.app')
@section('title', 'Dashboard')
@section('deskripsi', 'Ringkasan kondisi sekolah hari ini, ' . now()->translatedFormat('l d F Y') . '.')

@section('content')
@php
    $user = auth()->user();
    $bolehLihat = fn ($route) => \App\Support\Navigasi::bolehAkses($route, $user);
@endphp

<div class="space-y-6">

    @if(!$tahunAjaran)
        <div class="alert alert-warning">
            <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
            <span class="flex-1">
                Belum ada Tahun Ajaran aktif — sebagian besar data tidak akan tampil.
                @if($bolehLihat('tahun-ajaran.index'))
                    <a href="{{ route('tahun-ajaran.index') }}" class="font-bold underline">Aktifkan sekarang</a>.
                @else
                    Hubungi Admin/Kurikulum untuk mengaktifkannya.
                @endif
            </span>
        </div>
    @endif

    {{-- ===== Ringkasan angka utama ===== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card color="indigo" icon="fa-user-graduate" label="Siswa Aktif" :value="$totalSiswa"
                     :hint="$totalSiswaLaki.' laki-laki · '.$totalSiswaPerempuan.' perempuan'"
                     :href="$bolehLihat('siswa.index') ? route('siswa.index') : null" />

        <x-stat-card color="amber" icon="fa-chalkboard-user" label="Guru" :value="$totalGuru"
                     :hint="$totalGuruAktif.' aktif · '.$totalGuruTidakAktif.' nonaktif'"
                     :href="$bolehLihat('users.index') ? route('users.index') : null" />

        <x-stat-card color="teal" icon="fa-school" label="Rombel Aktif" :value="$totalKelas"
                     :hint="$totalKelasTidakAktif.' rombel nonaktif'"
                     :href="$bolehLihat('kelas.index') ? route('kelas.index') : null" />

        <x-stat-card color="emerald" icon="fa-pen-to-square" label="Jurnal Hari Ini"
                     :value="$jurnalHariIni" :suffix="'/ '.$jadwalHariIni.' sesi'"
                     :hint="$persenJurnal.'% sesi hari ini sudah dijurnalkan'"
                     :href="$bolehLihat('rekap.index') ? route('rekap.index') : null" />
    </div>

    {{-- ===== Kondisi hari ini ===== --}}
    <div class="grid lg:grid-cols-3 gap-6">
        <x-panel judul="Kehadiran Siswa Hari Ini" ikon="fa-clipboard-check"
                 deskripsi="Status akhir tiap siswa (diambil dari jam pelajaran terakhir)."
                 class="lg:col-span-2">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach([
                    ['Hadir', 'emerald', 'fa-circle-check'],
                    ['Sakit', 'amber', 'fa-thermometer'],
                    ['Izin', 'sky', 'fa-hand'],
                    ['Alfa', 'rose', 'fa-flag'],
                ] as [$status, $color, $icon])
                    <div class="rounded-xl bg-{{ $color }}-50 border border-{{ $color }}-100 p-3 text-center">
                        <p class="text-lg text-{{ $color }}-500"><i class="fa-solid {{ $icon }}"></i></p>
                        <p class="text-2xl font-extrabold text-{{ $color }}-700 leading-tight">{{ $rekapHariIni[$status] ?? 0 }}</p>
                        <p class="text-xs font-semibold text-{{ $color }}-600">{{ $status }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 pt-4 border-t border-slate-100 flex items-center gap-3 text-sm">
                @if($kelasBelumDiabsen > 0)
                    <span class="badge bg-amber-50 text-amber-700"><i class="fa-solid fa-hourglass-half mr-1.5"></i> {{ $kelasBelumDiabsen }} kelas belum diabsen</span>
                    <span class="text-slate-400 text-xs">Pantau daftar lengkapnya di tabel bawah.</span>
                @else
                    <span class="badge bg-emerald-50 text-emerald-700"><i class="fa-solid fa-circle-check mr-1.5"></i> Semua kelas sudah diabsen</span>
                @endif
            </div>
        </x-panel>

        <x-panel judul="Kepatuhan Jurnal" ikon="fa-gauge-high" deskripsi="Sesi mengajar yang sudah dijurnalkan hari ini.">
            <div class="flex flex-col items-center justify-center py-2">
                @php
                    $keliling = 2 * M_PI * 52;
                    $isi = $keliling * (100 - $persenJurnal) / 100;
                    $warnaCincin = $persenJurnal >= 80 ? '#10b981' : ($persenJurnal >= 50 ? '#f59e0b' : '#f43f5e');
                @endphp
                <div class="relative w-36 h-36">
                    <svg viewBox="0 0 120 120" class="w-36 h-36 -rotate-90">
                        <circle cx="60" cy="60" r="52" fill="none" stroke="#eef2f7" stroke-width="12" />
                        <circle cx="60" cy="60" r="52" fill="none" stroke="{{ $warnaCincin }}" stroke-width="12"
                                stroke-linecap="round" stroke-dasharray="{{ round($keliling, 2) }}" stroke-dashoffset="{{ round($isi, 2) }}" />
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-3xl font-extrabold text-slate-800">{{ $persenJurnal }}%</span>
                    </div>
                </div>
                <p class="mt-3 text-sm text-slate-500">{{ $jurnalHariIni }} dari {{ $jadwalHariIni }} sesi</p>
            </div>
        </x-panel>
    </div>

    <x-alfa-widget :data="$siswaAlfaHariIni" />

    {{-- ===== Monitoring per kelas + tren ===== --}}
    <div class="grid lg:grid-cols-2 gap-6">
        <x-panel judul="Pengisian Absensi Per Kelas" ikon="fa-list-check" deskripsi="Hari ini" rapat>
            <div class="overflow-x-auto max-h-[420px]">
                <table class="table-clean">
                    <thead><tr><th>Kelas</th><th>Wali Kelas</th><th class="text-center">Terisi</th><th>Progres</th></tr></thead>
                    <tbody>
                        @forelse($rekapPerKelas as $r)
                        <tr>
                            <td class="font-semibold">
                                <div class="flex items-center gap-2">
                                    <x-initial-avatar :nama="$r['kelas']" />
                                    {{ $r['kelas'] }}
                                </div>
                            </td>
                            <td class="text-slate-500">{{ $r['wali_kelas'] }}</td>
                            <td class="text-center whitespace-nowrap">{{ $r['terisi'] }}/{{ $r['jumlah_siswa'] }}</td>
                            <td class="min-w-[130px]">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-1.5 rounded-full bg-slate-200 overflow-hidden min-w-[60px]">
                                        <div class="h-full rounded-full {{ $r['persentase'] >= 100 ? 'bg-emerald-500' : ($r['persentase'] > 0 ? 'bg-amber-500' : 'bg-slate-300') }}"
                                             style="width: {{ max($r['persentase'], 2) }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-slate-500 w-9 text-right">{{ $r['persentase'] }}%</span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="empty-state">Belum ada kelas aktif pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-panel>

        <x-panel judul="Tren Kehadiran 7 Hari" ikon="fa-chart-line" deskripsi="Jumlah catatan absensi per status.">
            <div class="flex items-center gap-3 mb-3 text-xs flex-wrap">
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Hadir</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Sakit</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-sky-500"></span> Izin</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Alfa</span>
            </div>
            @php
                $maxNilai = max(1, $statistikMingguan->flatMap(fn ($b) => [$b['Hadir'], $b['Sakit'], $b['Izin'], $b['Alfa']])->max());
                $lebar = 600; $tinggi = 170; $n = $statistikMingguan->count();
                $jarakX = $n > 1 ? $lebar / ($n - 1) : $lebar;
                $titik = function (string $kunci) use ($statistikMingguan, $maxNilai, $tinggi, $jarakX) {
                    return $statistikMingguan->values()
                        ->map(fn ($b, $i) => round($i * $jarakX, 1).','.round($tinggi - ($b[$kunci] / $maxNilai) * ($tinggi - 10), 1))
                        ->implode(' ');
                };
            @endphp
            <svg viewBox="0 0 {{ $lebar }} {{ $tinggi }}" class="w-full" preserveAspectRatio="none" style="height:190px">
                @foreach([0, 0.25, 0.5, 0.75, 1] as $g)
                    <line x1="0" y1="{{ $tinggi * $g }}" x2="{{ $lebar }}" y2="{{ $tinggi * $g }}" stroke="#eef2f7" stroke-width="1" />
                @endforeach
                <polyline points="{{ $titik('Hadir') }}" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linejoin="round" />
                <polyline points="{{ $titik('Sakit') }}" fill="none" stroke="#f59e0b" stroke-width="2.5" stroke-linejoin="round" />
                <polyline points="{{ $titik('Izin') }}" fill="none" stroke="#0ea5e9" stroke-width="2.5" stroke-linejoin="round" />
                <polyline points="{{ $titik('Alfa') }}" fill="none" stroke="#f43f5e" stroke-width="2.5" stroke-linejoin="round" />
            </svg>
            <div class="flex justify-between text-xs text-slate-400 mt-1">
                @foreach($statistikMingguan as $b)<span>{{ $b['label'] }}</span>@endforeach
            </div>
        </x-panel>
    </div>

    {{-- ===== Aksi cepat (hanya menu yang benar-benar boleh dibuka user ini) ===== --}}
    @php
        $kandidatAksi = [
            ['route' => 'rekap.index', 'icon' => 'fa-chart-line', 'label' => 'Rekapitulasi Kepatuhan', 'desc' => 'Kepatuhan jurnal & absensi per guru.', 'color' => 'emerald'],
            ['route' => 'laporan.absensi-guru', 'icon' => 'fa-user-clock', 'label' => 'Kehadiran Mengajar Guru', 'desc' => 'Siapa yang belum mengisi absensi.', 'color' => 'amber'],
            ['route' => 'walikelas.absensi-bulanan', 'icon' => 'fa-calendar-check', 'label' => 'Rekap Absensi Kelas', 'desc' => 'Rekap kehadiran bulanan per kelas.', 'color' => 'sky'],
            ['route' => 'bk.dashboard', 'icon' => 'fa-hand-holding-heart', 'label' => 'Ringkasan Pelanggaran', 'desc' => 'Kondisi kedisiplinan siswa.', 'color' => 'rose'],
            ['route' => 'users.index', 'icon' => 'fa-user-gear', 'label' => 'Kelola Pengguna', 'desc' => 'Akun guru & staf.', 'color' => 'violet'],
            ['route' => 'pengaturan-sekolah.edit', 'icon' => 'fa-gear', 'label' => 'Pengaturan Sekolah', 'desc' => 'Identitas sekolah untuk dokumen cetak.', 'color' => 'slate'],
        ];
        $aksiCepat = collect($kandidatAksi)->filter(fn ($a) => $bolehLihat($a['route']))->values();
    @endphp
    @if($aksiCepat->isNotEmpty())
        <div>
            <p class="section-title mb-3">Aksi Cepat</p>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($aksiCepat as $a)
                    <x-aksi-cepat :href="route($a['route'])" :icon="$a['icon']" :label="$a['label']" :deskripsi="$a['desc']" :color="$a['color']" />
                @endforeach
            </div>
        </div>
    @endif

    @if($checklistOnboarding)
        @include('dashboard.partials.onboarding-checklist', ['checklistOnboarding' => $checklistOnboarding])
    @endif

    <x-panel judul="Aktivitas Terbaru" ikon="fa-clock-rotate-left"
             deskripsi="Catatan terbaru dari jurnal, kasus BK, dan data siswa.">
        <div class="space-y-3">
            @forelse($aktivitasTerbaru as $a)
                <div class="flex items-start gap-3 text-sm">
                    <div class="w-8 h-8 rounded-lg bg-{{ $a['warna'] }}-50 text-{{ $a['warna'] }}-600 flex items-center justify-center text-xs shrink-0">
                        <i class="fa-solid {{ $a['ikon'] }}"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-slate-700 truncate">{{ $a['teks'] }}</p>
                        <p class="text-xs text-slate-400">{{ $a['waktu']?->diffForHumans() }}</p>
                    </div>
                    <span class="badge bg-slate-100 text-slate-500 shrink-0">{{ $a['tag'] }}</span>
                </div>
            @empty
                <p class="empty-state">Belum ada aktivitas tercatat.</p>
            @endforelse
        </div>
    </x-panel>
</div>
@endsection
