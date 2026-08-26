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
