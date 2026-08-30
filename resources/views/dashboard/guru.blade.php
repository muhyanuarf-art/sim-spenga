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

    {{-- =================================================================
         YANG HARUS ANDA KERJAKAN

         Ditaruh PALING ATAS dan ditulis sebagai kalimat perintah, bukan
         tabel berstatus. Panel jadwal di bawah tetap ada dan tetap
         berguna — tetapi ia menuntut guru membaca tanda, menafsirkannya,
         lalu menyimpulkan sendiri apa yang harus diklik. Bagian inilah
         yang menghapus langkah menyimpulkan itu: satu baris = satu
         pekerjaan = satu tombol.

         Ukuran hurufnya sengaja lebih besar daripada bagian lain
         (text-base, tombol setinggi 48px) karena pemakainya guru senior.
         ================================================================= --}}
    @php
        $namaDepan = \Illuminate\Support\Str::of(auth()->user()->name)->before(',')->trim();
        $jamSekarang = (int) now()->format('H');
        $sapaan = $jamSekarang < 11 ? 'Selamat pagi' : ($jamSekarang < 15 ? 'Selamat siang' : ($jamSekarang < 18 ? 'Selamat sore' : 'Selamat malam'));
        $tugasHariIni = $tugas->where('hari_ini', true);
        $tugasTertinggal = $tugas->where('hari_ini', false);
        $kegiatanBelum = $kegiatanHariIni->where('sudah_diisi', false);
        $jumlahTugas = $tugas->count() + $kegiatanBelum->count();
    @endphp

    <div class="card p-5 sm:p-6 {{ $jumlahTugas > 0 ? 'border-amber-200' : 'border-emerald-200' }}">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-2xl shrink-0 flex items-center justify-center text-xl
                {{ $jumlahTugas > 0 ? 'bg-amber-100 text-amber-600' : 'bg-emerald-100 text-emerald-600' }}">
                <i class="fa-solid {{ $jumlahTugas > 0 ? 'fa-clipboard-list' : 'fa-mug-hot' }}"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-lg font-bold text-slate-800">
                    {{ $sapaan }}, {{ $namaDepan }}.
                </p>

                @if($jumlahTugas > 0)
                    <p class="text-base text-slate-600 mt-0.5 leading-relaxed">
                        Ada <strong class="text-amber-700">{{ $jumlahTugas }} pekerjaan</strong> yang belum selesai.
                        Klik tombolnya satu per satu sampai daftar ini habis.
                    </p>
                @else
                    <p class="text-base text-slate-600 mt-0.5 leading-relaxed">
                        <strong class="text-emerald-700">Semua pekerjaan Anda sudah selesai.</strong>
                        Tidak ada yang perlu dikerjakan sekarang.
                    </p>
                @endif
            </div>
        </div>

        @if($jumlahTugas > 0)
            <div class="mt-5 space-y-3">

                {{-- Absensi kegiatan sekolah (wali kelas) didahulukan: hanya
                     wali kelas yang bisa mengisinya, dan pada hari kegiatan
                     biasanya tidak ada jadwal KBM sama sekali. --}}
                @foreach($kegiatanBelum as $item)
                    @php $k = $item['kegiatan']; @endphp
                    <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-4 flex items-center justify-between gap-4 flex-wrap">
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-wide text-amber-700 mb-1">
                                <i class="fa-solid fa-flag-checkered mr-1"></i> Hari ini · Kegiatan Sekolah
                            </p>
                            <p class="text-base font-bold text-slate-800 leading-tight">{{ $k->nama }}</p>
                            <p class="text-sm text-slate-600 mt-0.5">
                                Kelas {{ $kelasWali->nama_kelas }} — kehadirannya Anda yang mengisi sebagai wali kelas.
                            </p>
                        </div>
                        <a href="{{ route('kegiatan.absensi.form', ['kegiatan' => $k, 'kelas' => $kelasWali]) }}"
                           class="btn-primary h-12 px-5 text-base shrink-0">
                            <i class="fa-solid fa-pen-to-square mr-2"></i> Isi Sekarang
                        </a>
                    </div>
                @endforeach

                @foreach($tugas as $t)
                    <div class="rounded-xl border p-4 flex items-center justify-between gap-4 flex-wrap
                        {{ $t['hari_ini'] ? 'border-amber-200 bg-amber-50/60' : 'border-rose-200 bg-rose-50/60' }}">
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-wide mb-1
                                {{ $t['hari_ini'] ? 'text-amber-700' : 'text-rose-700' }}">
                                @if($t['hari_ini'])
                                    <i class="fa-solid fa-circle-exclamation mr-1"></i> Hari ini · belum diisi
                                @else
                                    <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                                    Tertinggal — {{ $t['tanggal']->translatedFormat('l, d F') }}
                                @endif
                            </p>
                            <p class="text-base font-bold text-slate-800 leading-tight">
                                Kelas {{ $t['kelas']->nama_kelas }} · {{ $t['mapel']->nama_mapel }}
                            </p>
                            <p class="text-sm text-slate-600 mt-0.5">
                                @if($t['jam_awal']->id === $t['jam_akhir']->id)
                                    Jam ke-{{ $t['jam_awal']->jam_ke }}
                                @else
                                    Jam ke-{{ $t['jam_awal']->jam_ke }}–{{ $t['jam_akhir']->jam_ke }}
                                @endif
                                ({{ substr($t['jam_awal']->jam_mulai, 0, 5) }}–{{ substr($t['jam_akhir']->jam_selesai, 0, 5) }})
                            </p>
                        </div>
                        <a href="{{ route('mengajar.form', $t['ids']) }}"
                           class="btn-primary h-12 px-5 text-base shrink-0">
                            <i class="fa-solid fa-pen-to-square mr-2"></i> Isi Jurnal &amp; Absensi
                        </a>
                    </div>
                @endforeach

                @if($tugasTertinggal->isNotEmpty())
                    <p class="text-sm text-slate-500 pt-1">
                        <i class="fa-solid fa-circle-info mr-1 text-slate-400"></i>
                        Baris merah adalah hari yang sudah lewat tetapi jurnalnya belum diisi. Masih bisa diisi sekarang.
                    </p>
                @endif
            </div>
        @endif

        {{-- Tombol besar untuk dua pekerjaan yang paling sering dibuka.
             Sengaja HANYA dua (tiga untuk wali kelas), bukan seluruh menu:
             menyalin sidebar ke tengah layar tidak mengurangi kebingungan,
             hanya memindahkannya. --}}
        <div class="mt-5 pt-5 border-t border-slate-100 grid sm:grid-cols-2 {{ $kelasWali ? 'lg:grid-cols-3' : '' }} gap-3">
            <a href="{{ route('mengajar.index') }}"
               class="flex items-center gap-3 rounded-xl border border-slate-200 hover:border-brand-300 hover:bg-brand-50/50 p-4 transition">
                <span class="w-11 h-11 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid fa-clipboard-check"></i>
                </span>
                <span class="min-w-0">
                    <span class="block text-base font-bold text-slate-800">Absensi &amp; Jurnal</span>
                    <span class="block text-sm text-slate-500">Semua jadwal mengajar Anda</span>
                </span>
            </a>

            <a href="{{ route('nilai.pilih') }}"
               class="flex items-center gap-3 rounded-xl border border-slate-200 hover:border-brand-300 hover:bg-brand-50/50 p-4 transition">
                <span class="w-11 h-11 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid fa-pen-ruler"></i>
                </span>
                <span class="min-w-0">
                    <span class="block text-base font-bold text-slate-800">Daftar Nilai</span>
                    <span class="block text-sm text-slate-500">Isi nilai siswa per mata pelajaran</span>
                </span>
            </a>

            @if($kelasWali)
                <a href="{{ route('walikelas.absensi-bulanan') }}"
                   class="flex items-center gap-3 rounded-xl border border-slate-200 hover:border-brand-300 hover:bg-brand-50/50 p-4 transition">
                    <span class="w-11 h-11 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center text-lg shrink-0">
                        <i class="fa-solid fa-calendar-check"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-base font-bold text-slate-800">Rekap Kelas {{ $kelasWali->nama_kelas }}</span>
                        <span class="block text-sm text-slate-500">Kehadiran siswa perwalian Anda</span>
                    </span>
                </a>
            @endif
        </div>
    </div>

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
