@extends('layouts.app')
@section('title', 'Dashboard')
@section('deskripsi', 'Pantauan kehadiran & penanganan kasus di kelas binaan Anda, ' . now()->translatedFormat('l d F Y') . '.')

@section('content')
<div class="space-y-6">

    @if($kelasBk->isEmpty())
        <div class="alert alert-warning">
            <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
            <span class="flex-1">
                Anda belum dipetakan ke kelas mana pun, sehingga data monitoring belum bisa ditampilkan.
                Hubungi Kurikulum/Admin untuk mengaturnya lewat menu <b>Pemetaan Guru BK</b>.
            </span>
        </div>
    @else
        {{-- =============================================================
             YANG HARUS ANDA KERJAKAN

             Pola yang sama dengan dashboard guru: pekerjaan lebih dulu,
             laporan belakangan. Satu baris = satu pekerjaan = satu
             tombol, tanpa ada yang perlu ditafsirkan sendiri.

             Bedanya dengan dashboard guru, pekerjaan BK tidak punya
             tenggat harian — kasus yang belum ditangani sejak bulan lalu
             tetap harus ditangani. Karena itu yang dibatasi bukan
             umurnya, melainkan jumlah yang ditampilkan; lihat
             DashboardController::tugasBk().
             ============================================================= --}}
        @php
            $namaDepan = \Illuminate\Support\Str::of(auth()->user()->name)->before(',')->trim();
            $jamSekarang = (int) now()->format('H');
            $sapaan = $jamSekarang < 11 ? 'Selamat pagi' : ($jamSekarang < 15 ? 'Selamat siang' : ($jamSekarang < 18 ? 'Selamat sore' : 'Selamat malam'));
            $jumlahTugas = $tugas->count() + $sisaTugas;
        @endphp

        <div class="card p-5 sm:p-6 {{ $jumlahTugas > 0 ? 'border-amber-200' : 'border-emerald-200' }}">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl shrink-0 flex items-center justify-center text-xl
                    {{ $jumlahTugas > 0 ? 'bg-amber-100 text-amber-600' : 'bg-emerald-100 text-emerald-600' }}">
                    <i class="fa-solid {{ $jumlahTugas > 0 ? 'fa-clipboard-list' : 'fa-mug-hot' }}"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-base sm:text-lg font-bold text-slate-800">{{ $sapaan }}, {{ $namaDepan }}.</p>
                    @if($jumlahTugas > 0)
                        <p class="text-base text-slate-600 mt-0.5 leading-relaxed">
                            Ada <strong class="text-amber-700">{{ $jumlahTugas }} pekerjaan</strong> yang menunggu ditangani.
                            Klik tombolnya satu per satu sampai daftar ini habis.
                        </p>
                    @else
                        <p class="text-base text-slate-600 mt-0.5 leading-relaxed">
                            <strong class="text-emerald-700">Tidak ada pekerjaan yang tertunda.</strong>
                            Semua kasus, pembinaan, dan pemanggilan sudah ditangani.
                        </p>
                    @endif
                </div>
            </div>

            @if($tugas->isNotEmpty())
                <div class="mt-5 space-y-3">
                    @foreach($tugas as $t)
                        @php
                            $warna = match($t['jenis']) {
                                'kasus' => ['border-rose-200 bg-rose-50/60', 'text-rose-700', 'fa-triangle-exclamation', 'Kasus baru belum ditangani'],
                                'evaluasi' => ['border-amber-200 bg-amber-50/60', 'text-amber-700', 'fa-clock-rotate-left', 'Evaluasi pembinaan jatuh tempo'],
                                default => ['border-sky-200 bg-sky-50/60', 'text-sky-700', 'fa-people-arrows', 'Hasil pemanggilan belum dicatat'],
                            };
                        @endphp
                        <div class="rounded-xl border p-4 flex items-center justify-between gap-4 flex-wrap {{ $warna[0] }}">
                            <div class="min-w-0">
                                <p class="text-xs font-bold uppercase tracking-wide mb-1 {{ $warna[1] }}">
                                    <i class="fa-solid {{ $warna[2] }} mr-1"></i> {{ $warna[3] }}
                                    @if($t['tanggal'])
                                        · {{ \Illuminate\Support\Carbon::parse($t['tanggal'])->translatedFormat('d M Y') }}
                                    @endif
                                </p>
                                <p class="text-base font-bold text-slate-800 leading-tight">{{ $t['judul'] }}</p>
                                <p class="text-sm text-slate-600 mt-0.5">{{ $t['rincian'] }}</p>
                            </div>
                            <a href="{{ $t['url'] }}" class="btn-primary h-12 px-5 text-base shrink-0">
                                <i class="fa-solid fa-pen-to-square mr-2"></i> {{ $t['tombol'] }}
                            </a>
                        </div>
                    @endforeach

                    @if($sisaTugas > 0)
                        <p class="text-sm text-slate-500 pt-1">
                            <i class="fa-solid fa-circle-info mr-1 text-slate-400"></i>
                            Masih ada <strong>{{ $sisaTugas }} pekerjaan lain</strong> yang tidak ditampilkan di sini supaya
                            daftarnya tetap bisa dikerjakan. Selesaikan yang di atas dulu, sisanya akan naik menggantikannya —
                            atau buka <a href="{{ route('bk.kasus.index') }}" class="text-brand-600 font-semibold hover:underline">daftar lengkapnya</a>.
                        </p>
                    @endif
                </div>
            @endif

            {{-- Pintasan besar untuk pekerjaan harian BK. Sengaja tiga,
                 bukan seluruh menu — menyalin sidebar ke tengah layar
                 hanya memindahkan kebingungan, tidak menguranginya. --}}
            <div class="mt-5 pt-5 border-t border-slate-100 grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach([
                    ['bk.kasus.create', 'fa-file-circle-plus', 'Catat Kasus', 'Laporkan pelanggaran siswa'],
                    ['bk.siswa.index', 'fa-address-book', 'Rekam Jejak Siswa', 'Riwayat lengkap per siswa'],
                    ['bk.pembinaan.create', 'fa-hand-holding-heart', 'Catat Pembinaan', 'Tindak lanjut atas sebuah kasus'],
                ] as [$rute, $ikon, $judul, $ket])
                    <a href="{{ route($rute) }}"
                       class="flex items-center gap-3 rounded-xl border border-slate-200 hover:border-brand-300 hover:bg-brand-50/50 p-4 transition">
                        <span class="w-11 h-11 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center text-lg shrink-0">
                            <i class="fa-solid {{ $ikon }}"></i>
                        </span>
                        <span class="min-w-0">
                            <span class="block text-base font-bold text-slate-800">{{ $judul }}</span>
                            <span class="block text-sm text-slate-500">{{ $ket }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-card color="indigo" icon="fa-school" label="Kelas Binaan" :value="$kelasBk->count()"
                         :hint="$totalSiswaBinaan.' siswa'" />
            <x-stat-card :color="$siswaAlfaHariIni->count() > 0 ? 'rose' : 'emerald'" icon="fa-flag"
                         label="Alfa Hari Ini" :value="$siswaAlfaHariIni->count()" suffix="siswa" />
            <x-stat-card color="amber" icon="fa-triangle-exclamation" label="Kasus Bulan Ini" :value="$kasusBulanIni"
                         :hint="now()->translatedFormat('F Y')" :href="route('bk.kasus.index')" />
            <x-stat-card color="violet" icon="fa-hand-holding-heart" label="Sedang Dibina" :value="$siswaDalamPembinaan"
                         suffix="siswa" :href="route('bk.pembinaan.index')" />
        </div>

        @if($pemanggilanMenunggu > 0)
            <div class="alert alert-info">
                <i class="fa-solid fa-bell mt-0.5"></i>
                <span class="flex-1">
                    Ada <b>{{ $pemanggilanMenunggu }}</b> pemanggilan orang tua yang menunggu hasil pertemuan.
                    <a href="{{ route('bk.pemanggilan.index') }}" class="font-bold underline">Buka daftarnya</a>.
                </span>
            </div>
        @endif

        <x-panel judul="Kondisi Kehadiran Kelas Binaan" ikon="fa-list-check"
                 deskripsi="Klik kelas untuk melihat rekap absensi bulanannya.">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                @foreach($rekapPerKelasBk as $r)
                <a href="{{ route('walikelas.absensi-bulanan', $r['kelas']) }}"
                   class="rounded-xl border p-4 block transition hover:shadow-md
                        {{ $r['alfa_hari_ini'] > 0 ? 'border-rose-200 bg-rose-50/60 hover:border-rose-300' : 'border-slate-200 bg-white hover:border-emerald-300' }}">
                    <p class="font-bold text-slate-800">Kelas {{ $r['kelas']->nama_kelas }}</p>
                    <p class="text-xs text-slate-400 mb-2">{{ $r['total_siswa'] }} siswa</p>
                    @if($r['alfa_hari_ini'] > 0)
                        <span class="badge bg-rose-100 text-rose-700"><i class="fa-solid fa-flag mr-1.5"></i> {{ $r['alfa_hari_ini'] }} Alfa</span>
                    @else
                        <span class="badge bg-emerald-100 text-emerald-700"><i class="fa-solid fa-circle-check mr-1.5"></i> Aman</span>
                    @endif
                </a>
                @endforeach
            </div>
        </x-panel>

        <x-alfa-widget :data="$siswaAlfaHariIni" title="Siswa Alfa Hari Ini — Kelas Binaan Anda" />
    @endif

    <div>
        <p class="section-title mb-3">Aksi Cepat</p>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <x-aksi-cepat :href="route('bk.kasus.create')" icon="fa-plus" color="rose"
                          label="Catat Kasus Baru" deskripsi="Laporkan pelanggaran siswa." />
            <x-aksi-cepat :href="route('bk.dashboard')" icon="fa-chart-pie" color="violet"
                          label="Ringkasan Pelanggaran" deskripsi="Sebaran tahap & siswa perlu perhatian." />
            <x-aksi-cepat :href="route('bk.pemanggilan.index')" icon="fa-users" color="amber"
                          label="Pemanggilan Orang Tua" deskripsi="Agenda & hasil pertemuan." />
            <x-aksi-cepat :href="route('surat.create')" icon="fa-envelope-open-text" color="brand"
                          label="Buat Surat" deskripsi="Surat BK dengan penomoran otomatis." />
            <x-aksi-cepat :href="route('bk.siswa.index')" icon="fa-user-shield" color="indigo"
                          label="Profil Poin Siswa" deskripsi="Rekam jejak perilaku tiap siswa." />
            <x-aksi-cepat :href="route('walikelas.absensi-bulanan')" icon="fa-calendar-check" color="sky"
                          label="Rekap Absensi Kelas" deskripsi="Rekap kehadiran bulanan per kelas." />
        </div>
    </div>
</div>
@endsection
