@extends('layouts.app')
@section('title', 'Detail Kegiatan')
@section('deskripsi', $kegiatan->nama)

@section('aksi')
    <a href="{{ route('kegiatan.index') }}" class="btn-outline">&larr; Kembali ke daftar</a>
@endsection

@section('content')
@php
    $totalTarget = $kelasSasaran->count() * $tanggalList->count();
    $totalTerisi = $absensiTerisi->count();
    $persen = $totalTarget > 0 ? (int) round(min($totalTerisi, $totalTarget) / $totalTarget * 100) : 0;
    $totalAlfa = $absensiTerisi->sum('jumlah_alfa');
@endphp

<div class="space-y-6">

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card color="brand" icon="fa-calendar-day" label="Hari Kegiatan" :value="$tanggalList->count()"
                     :hint="$kegiatan->rentangLabel()" />
        <x-stat-card color="indigo" icon="fa-school" label="Kelas Sasaran" :value="$kelasSasaran->count()"
                     :hint="$kegiatan->cakupanLabel()" />
        <x-stat-card :color="$persen >= 100 ? 'emerald' : 'amber'" icon="fa-clipboard-check" label="Absensi Terisi"
                     :value="$totalTerisi" :suffix="'/ '.$totalTarget" :hint="$persen.'% dari target'" />
        <x-stat-card :color="$totalAlfa > 0 ? 'rose' : 'emerald'" icon="fa-flag" label="Total Alfa" :value="$totalAlfa"
                     hint="Selama kegiatan ini" />
    </div>

    <x-panel judul="Informasi Kegiatan" ikon="fa-circle-info">
        <div class="grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
            <div><span class="text-slate-400">Status</span><br><span class="badge {{ $kegiatan->statusBadgeClass() }}">{{ $kegiatan->statusLabel() }}</span></div>
            <div><span class="text-slate-400">Jenis</span><br><span class="font-medium text-slate-700">{{ $kegiatan->jenisLabel() }}</span></div>
            <div><span class="text-slate-400">Tanggal</span><br><span class="font-medium text-slate-700">{{ $kegiatan->rentangLabel() }}</span></div>
            <div>
                <span class="text-slate-400">Hari berlangsung</span><br>
                <span class="font-medium text-slate-700">{{ $kegiatan->hari_aktif ? implode(', ', $kegiatan->hari_aktif) : 'Setiap hari dalam rentang' }}</span>
            </div>
            <div>
                <span class="text-slate-400">Notifikasi WhatsApp Alfa</span><br>
                @if($kegiatan->kirim_wa_alfa)
                    <span class="badge bg-emerald-50 text-emerald-700">Aktif</span>
                @else
                    <span class="badge bg-slate-100 text-slate-500">Dimatikan</span>
                @endif
            </div>
            <div><span class="text-slate-400">Dijadwalkan oleh</span><br><span class="font-medium text-slate-700">{{ $kegiatan->dibuatOleh->name ?? '—' }}</span></div>
            @if($kegiatan->keterangan)
                <div class="sm:col-span-2"><span class="text-slate-400">Keterangan</span><br><span class="text-slate-600">{{ $kegiatan->keterangan }}</span></div>
            @endif
        </div>
    </x-panel>

    <x-panel judul="Pantauan Pengisian Absensi" ikon="fa-list-check"
             deskripsi="Baris = kelas sasaran, kolom = hari kegiatan. Yang mengisi adalah wali kelas masing-masing." rapat>
        @if($kelasSasaran->isEmpty() || $tanggalList->isEmpty())
            <p class="empty-state">Belum ada kelas sasaran atau tanggal kegiatan yang valid.</p>
        @else
            <div class="overflow-x-auto">
                <table class="table-clean">
                    <thead>
                        <tr>
                            <th class="sticky left-0 bg-slate-50 z-10">Kelas</th>
                            <th>Wali Kelas</th>
                            @foreach($tanggalList as $tgl)
                                <th class="text-center whitespace-nowrap">{{ $tgl->translatedFormat('D d/m') }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($kelasSasaran as $kls)
                        <tr>
                            <td class="font-semibold sticky left-0 bg-white z-10">{{ $kls->nama_kelas }}</td>
                            <td class="text-slate-500">{{ $kls->waliKelas->name ?? '— belum ada wali kelas —' }}</td>
                            @foreach($tanggalList as $tgl)
                                @php $a = $absensiTerisi->get($kls->id.'|'.$tgl->toDateString()); @endphp
                                <td class="text-center">
                                    @if($a)
                                        <span class="badge bg-emerald-50 text-emerald-700"
                                              title="Diisi {{ $a->diisiOleh->name ?? '—' }} · H{{ $a->jumlah_hadir }} S{{ $a->jumlah_sakit }} I{{ $a->jumlah_izin }} A{{ $a->jumlah_alfa }}">
                                            <i class="fa-solid fa-check mr-1"></i>{{ $a->jumlah_alfa > 0 ? $a->jumlah_alfa.' alfa' : 'OK' }}
                                        </span>
                                    @elseif($tgl->isFuture())
                                        <span class="text-slate-300">—</span>
                                    @else
                                        <span class="badge bg-amber-50 text-amber-700">Belum</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-panel>

    @if($kelasSasaran->contains(fn ($k) => ! $k->waliKelas))
        <div class="alert alert-warning">
            <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
            <span class="flex-1">
                Ada kelas sasaran yang <b>belum punya wali kelas</b>. Karena absensi kegiatan hanya boleh diisi wali kelas,
                kelas tersebut tidak akan bisa diabsen sampai wali kelasnya ditetapkan lewat menu Data Kelas.
            </span>
        </div>
    @endif
</div>
@endsection
