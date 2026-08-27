@extends('layouts.app')
@section('title', 'Dashboard')
@section('deskripsi', 'Selamat datang, ' . auth()->user()->name . '. Ini pekerjaan Anda hari ini, ' . now()->translatedFormat('l d F Y') . '.')

@section('content')
<div class="space-y-6">

    @if(!$tahunAjaran)
        <div class="alert alert-warning">
            <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
            <span class="flex-1">Belum ada Tahun Ajaran aktif, jadwal mengajar tidak dapat ditampilkan. Hubungi Admin/Kurikulum.</span>
        </div>
    @endif

    {{-- ===== Ringkasan pekerjaan hari ini ===== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card color="sky" icon="fa-calendar-day" label="Sesi Hari Ini" :value="$totalSesiHariIni" suffix="sesi" />
        <x-stat-card color="emerald" icon="fa-circle-check" label="Sudah Diisi" :value="$sesiTerisiHariIni" :suffix="'/ '.$totalSesiHariIni" />
        <x-stat-card :color="$sesiBelumTerisi > 0 ? 'amber' : 'slate'" icon="fa-hourglass-half" label="Belum Diisi" :value="$sesiBelumTerisi"
                     :href="$sesiBelumTerisi > 0 ? route('mengajar.index') : null" />
        <x-stat-card color="violet" icon="fa-book" label="Jurnal Bulan Ini" :value="$jurnalBulanIni"
                     :hint="now()->translatedFormat('F Y')" />
    </div>

    {{-- Kegiatan sekolah hari ini (di luar jam KBM) — hanya wali kelas
         yang berhak mengisinya, jadi ditonjolkan di paling atas supaya
         tidak terlewat pada hari yang tidak ada jadwal KBM sama sekali. --}}
    @if($kegiatanHariIni->isNotEmpty())
        <x-panel judul="Kegiatan Sekolah Hari Ini" ikon="fa-flag-checkered"
                 deskripsi="Absensi kegiatan ini menjadi tanggung jawab Anda sebagai wali kelas.">
            <x-slot:aksi>
                <a href="{{ route('kegiatan.absensi.pilih') }}" class="btn-outline">Semua kegiatan</a>
            </x-slot:aksi>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($kegiatanHariIni as $item)
                    @php $k = $item['kegiatan']; @endphp
                    <a href="{{ route('kegiatan.absensi.form', ['kegiatan' => $k, 'kelas' => $kelasWali]) }}"
                       class="rounded-xl border p-4 block transition hover:shadow-md
                            {{ $item['sudah_diisi'] ? 'border-emerald-200 bg-emerald-50/50' : 'border-amber-200 bg-amber-50/50 hover:border-amber-300' }}">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="badge bg-white text-slate-600">{{ $k->jenisLabel() }}</span>
                            @if($item['sudah_diisi'])
                                <span class="badge bg-emerald-100 text-emerald-700"><i class="fa-solid fa-check mr-1"></i> Terisi</span>
                            @else
                                <span class="badge bg-amber-100 text-amber-700">Perlu diisi</span>
                            @endif
                        </div>
                        <p class="font-bold text-slate-800 leading-tight">{{ $k->nama }}</p>
                        <p class="text-sm text-slate-500">Kelas {{ $kelasWali->nama_kelas }}</p>
                    </a>
                @endforeach
            </div>
        </x-panel>
    @endif

    @if($kelasWali)
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-brand-700 via-brand-600 to-indigo-500 text-white px-5 py-4 flex items-center justify-between flex-wrap gap-3 shadow-lg shadow-brand-500/20">
            <div class="relative z-10">
                <p class="font-bold flex items-center gap-2"><i class="fa-solid fa-graduation-cap"></i> Wali Kelas {{ $kelasWali->nama_kelas }}</p>
                <p class="text-sm text-white/80">Pantau kehadiran dan jurnal mengajar kelas Anda.</p>
            </div>
            <div class="relative z-10 flex gap-2 flex-wrap">
                <a href="{{ route('walikelas.absensi-bulanan') }}" class="btn-outline bg-white/95 border-transparent">
                    <i class="fa-solid fa-calendar-check"></i> Rekap Absensi
                </a>
                <a href="{{ route('walikelas.jurnal-kelas') }}" class="btn-outline bg-white/95 border-transparent">
                    <i class="fa-solid fa-book-open"></i> Jurnal Kelas
                </a>
            </div>
            <div class="absolute -right-6 -bottom-10 w-40 h-40 rounded-full bg-white/10 blur-2xl"></div>
        </div>

        <x-alfa-widget :data="$siswaAlfaHariIni" :title="'Siswa Alfa Hari Ini — Kelas '.$kelasWali->nama_kelas" :show-kelas="false" />
    @endif

    {{-- ===== Jadwal mengajar hari ini ===== --}}
    <x-panel judul="Jadwal Mengajar Hari Ini" ikon="fa-clipboard-check"
             deskripsi="Klik kartu untuk mengisi absensi siswa & jurnal mengajar.">
        <x-slot:aksi>
            <a href="{{ route('mengajar.index') }}" class="btn-outline">Lihat semua jadwal</a>
        </x-slot:aksi>

        @if($jadwalHariIni->isEmpty())
            <p class="empty-state">
                <i class="fa-solid fa-mug-hot text-2xl block mb-2 text-slate-300"></i>
                Tidak ada jadwal mengajar untuk hari ini.
            </p>
        @else
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($jadwalHariIni as $sesi)
                    @php $terisi = $sesi['sudah_diisi'] ?? false; @endphp
                    <a href="{{ route('mengajar.form', $sesi['ids']) }}"
                       class="relative overflow-hidden rounded-xl border p-4 block transition
                            {{ $terisi ? 'border-emerald-200 bg-emerald-50/50 hover:border-emerald-300' : 'border-slate-200 bg-white hover:border-brand-300 hover:shadow-md' }}">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="badge {{ $terisi ? 'bg-emerald-100 text-emerald-700' : 'bg-brand-50 text-brand-700' }}">
                                <i class="fa-solid fa-clock mr-1.5"></i>
                                @if($sesi['jam_awal']->id === $sesi['jam_akhir']->id)
                                    {{ $sesi['jam_awal']->label }}
                                @else
                                    Jam ke-{{ $sesi['jam_awal']->jam_ke }}–{{ $sesi['jam_akhir']->jam_ke }}
                                @endif
                            </span>
                            @if($terisi)
                                <span class="badge bg-emerald-100 text-emerald-700"><i class="fa-solid fa-check mr-1"></i> Terisi</span>
                            @else
                                <span class="badge bg-amber-100 text-amber-700">Belum diisi</span>
                            @endif
                        </div>
                        <p class="font-bold text-slate-800">Kelas {{ $sesi['kelas']->nama_kelas }}</p>
                        <p class="text-sm text-slate-500">{{ $sesi['mapel']->nama_mapel }}</p>
                    </a>
                @endforeach
            </div>
        @endif
    </x-panel>

    {{-- ===== Jurnal terakhir ===== --}}
    <x-panel judul="Jurnal Terakhir Saya" ikon="fa-book" deskripsi="5 jurnal mengajar terakhir yang Anda isi." rapat>
        <div class="overflow-x-auto">
            <table class="table-clean">
                <thead><tr><th>Tanggal</th><th>Kelas</th><th>Mapel</th><th>Materi</th></tr></thead>
                <tbody>
                    @forelse($jurnalTerakhir as $j)
                    <tr>
                        <td class="text-slate-500 whitespace-nowrap">{{ $j->tanggal->translatedFormat('d M Y') }}</td>
                        <td><x-kelas-badge :nama="$j->kelas->nama_kelas" /></td>
                        <td><x-mapel-badge :nama="$j->mapel->nama_mapel" /></td>
                        <td class="text-slate-500">{{ \Illuminate\Support\Str::limit($j->materi, 60) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="empty-state">Belum ada jurnal yang Anda isi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-panel>
</div>
@endsection
