@extends('layouts.app')
@section('title', 'Dashboard')
@section('deskripsi', 'Pantauan kehadiran & kedisiplinan siswa se-sekolah, ' . now()->translatedFormat('l d F Y') . '.')

@section('content')
<div class="space-y-6">

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card color="indigo" icon="fa-user-graduate" label="Siswa Aktif" :value="$totalSiswa" />
        <x-stat-card :color="$siswaAlfaHariIni->count() > 0 ? 'rose' : 'emerald'" icon="fa-flag"
                     label="Alfa Hari Ini" :value="$siswaAlfaHariIni->count()" suffix="siswa" />
        <x-stat-card :color="$kelasBermasalah > 0 ? 'amber' : 'emerald'" icon="fa-school"
                     label="Kelas Ada Alfa" :value="$kelasBermasalah" :suffix="'/ '.$rekapPerKelas->count()" />
        <x-stat-card color="violet" icon="fa-triangle-exclamation" label="Kasus Bulan Ini" :value="$kasusBulanIni"
                     :hint="now()->translatedFormat('F Y')" :href="route('bk.dashboard')" />
    </div>

    @if($rekapPerKelas->isEmpty())
        <div class="alert alert-warning">
            <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
            <span class="flex-1">Belum ada data kelas pada periode aktif. Hubungi Kurikulum/Admin.</span>
        </div>
    @else
        <x-panel judul="Kondisi Kehadiran Per Kelas" ikon="fa-list-check"
                 deskripsi="Klik kelas untuk melihat rekap absensi bulanannya.">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                @foreach($rekapPerKelas as $r)
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

        <x-alfa-widget :data="$siswaAlfaHariIni" title="Siswa Alfa Hari Ini — Seluruh Sekolah" />
    @endif

    <div>
        <p class="section-title mb-3">Aksi Cepat</p>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <x-aksi-cepat :href="route('walikelas.absensi-bulanan')" icon="fa-calendar-check" color="sky"
                          label="Rekap Absensi Kelas" deskripsi="Rekap kehadiran bulanan, bisa ganti kelas." />
            <x-aksi-cepat :href="route('bk.dashboard')" icon="fa-hand-holding-heart" color="rose"
                          label="Ringkasan Pelanggaran" deskripsi="Kondisi kedisiplinan & siswa perlu perhatian." />
            <x-aksi-cepat :href="route('ekstrakurikuler.index')" icon="fa-people-group" color="violet"
                          label="Ekstrakurikuler" :deskripsi="$totalEkskulAktif.' kegiatan aktif'" />
            <x-aksi-cepat :href="route('ekstrakurikuler.absensi.pilih')" icon="fa-person-running" color="teal"
                          label="Absensi Ekstrakurikuler" deskripsi="Isi kehadiran peserta kegiatan." />
            <x-aksi-cepat :href="route('notifikasi-wa.index')" icon="fa-comment-sms" color="emerald"
                          label="Notifikasi WhatsApp Ortu" deskripsi="Status pesan siswa Alfa ke orang tua." />
            <x-aksi-cepat :href="route('bk.siswa.index')" icon="fa-user-shield" color="amber"
                          label="Profil Poin Siswa" deskripsi="Rekam jejak perilaku tiap siswa." />
        </div>
    </div>
</div>
@endsection
