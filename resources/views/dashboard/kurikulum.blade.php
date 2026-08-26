@extends('layouts.app')
@section('title', 'Dashboard')
@section('deskripsi', 'Monitoring kegiatan mengajar seluruh guru, ' . now()->translatedFormat('l d F Y') . '.')

@section('content')
<div class="space-y-6">

    @if(!$tahunAjaran)
        <div class="alert alert-warning">
            <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
            <span class="flex-1">Belum ada Tahun Ajaran aktif.
                <a href="{{ route('tahun-ajaran.index') }}" class="font-bold underline">Aktifkan sekarang</a>.
            </span>
        </div>
    @endif

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card color="sky" icon="fa-calendar-days" label="Sesi Terjadwal" :value="$totalJadwalHariIni" suffix="jam"
                     hint="Jadwal hari ini" :href="route('jadwal.index')" />
        <x-stat-card color="emerald" icon="fa-circle-check" label="Jurnal Terisi" :value="$totalJurnalHariIni"
                     :suffix="'/ '.$totalJadwalHariIni" :hint="$persenJurnal.'% kepatuhan hari ini'" />
        <x-stat-card color="amber" icon="fa-chalkboard-user" label="Total Guru" :value="$totalGuru" />
        <x-stat-card color="violet" icon="fa-diagram-project" label="Pemetaan Mengajar" :value="$totalMappingKelas"
                     hint="Kombinasi guru–mapel–kelas" :href="route('kurikulum.guru-mengajar.index')" />
    </div>

    {{-- ===== Guru yang belum mengisi jurnal: inti monitoring guru ===== --}}
    <x-panel ikon="fa-user-clock" judul="Guru Belum Mengisi Jurnal Hari Ini"
             deskripsi="Guru yang punya jadwal hari ini tetapi belum satu pun mengisi jurnal.">
        <x-slot:aksi>
            <a href="{{ route('laporan.absensi-guru') }}" class="btn-outline">Laporan lengkap</a>
        </x-slot:aksi>

        @if($guruBelumMengisi->isEmpty())
            <p class="text-sm text-emerald-600 font-medium py-2">
                <i class="fa-solid fa-circle-check mr-1.5"></i>
                Semua guru yang terjadwal hari ini sudah mengisi jurnal.
            </p>
        @else
            <div class="flex flex-wrap gap-2">
                @foreach($guruBelumMengisi as $g)
                    <span class="inline-flex items-center gap-2 rounded-lg bg-amber-50 border border-amber-100 px-3 py-1.5 text-sm font-medium text-amber-800">
                        <x-initial-avatar :nama="$g->name" /> {{ $g->name }}
                    </span>
                @endforeach
            </div>
            <p class="text-xs text-slate-400 mt-3">
                {{ $guruBelumMengisi->count() }} guru belum mengisi. Angka ini dihitung ulang otomatis setiap halaman dimuat.
            </p>
        @endif
    </x-panel>

    <x-alfa-widget :data="$siswaAlfaHariIni" />

    <x-panel judul="Jurnal Mengajar Masuk Hari Ini" ikon="fa-pen-to-square" deskripsi="10 pengisian terbaru." rapat>
        <div class="overflow-x-auto">
            <table class="table-clean">
                <thead><tr><th>Guru</th><th>Kelas</th><th>Mapel</th><th>Materi</th></tr></thead>
                <tbody>
                    @forelse($jurnalHariIni as $j)
                    <tr>
                        <td class="font-medium">
                            <div class="flex items-center gap-2">
                                <x-initial-avatar :nama="$j->guru->name" />
                                {{ $j->guru->name }}
                            </div>
                        </td>
                        <td><x-kelas-badge :nama="$j->kelas->nama_kelas" /></td>
                        <td><x-mapel-badge :nama="$j->mapel->nama_mapel" /></td>
                        <td class="text-slate-500">{{ \Illuminate\Support\Str::limit($j->materi, 60) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="empty-state">Belum ada jurnal yang diisi hari ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-panel>

    <div>
        <p class="section-title mb-3">Aksi Cepat</p>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <x-aksi-cepat :href="route('kurikulum.guru-mengajar.index')" icon="fa-diagram-project" color="violet"
                          label="Pemetaan Guru Mengajar" deskripsi="Atur guru mengajar mapel apa di kelas mana." />
            <x-aksi-cepat :href="route('jadwal.index')" icon="fa-calendar-days" color="sky"
                          label="Jadwal Pelajaran" deskripsi="Susun jadwal manual atau import Excel." />
            <x-aksi-cepat :href="route('rekap.index')" icon="fa-chart-line" color="emerald"
                          label="Rekapitulasi Kepatuhan" deskripsi="Persentase kepatuhan per guru & kelas." />
            <x-aksi-cepat :href="route('laporan.jurnal-guru')" icon="fa-book-open" color="amber"
                          label="Jurnal Mengajar Guru" deskripsi="Materi yang diajarkan tiap guru." />
            <x-aksi-cepat :href="route('siswa.index')" icon="fa-user-graduate" color="indigo"
                          label="Data Siswa" deskripsi="Data induk siswa & WhatsApp orang tua." />
            <x-aksi-cepat :href="route('tahun-ajaran.index')" icon="fa-calendar-plus" color="teal"
                          label="Tahun Ajaran" deskripsi="Buat, aktifkan, atau kunci periode." />
        </div>
    </div>

    @include('dashboard.partials.onboarding-checklist', ['checklistOnboarding' => $checklistOnboarding])
</div>
@endsection
