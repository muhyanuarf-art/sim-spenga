@extends('layouts.app')
@section('title', 'Daftar Nilai')

@section('content')
@php
    // Untuk guru mapel, halaman ini adalah "daftar pekerjaan": tiap baris
    // satu lembar daftar nilai (1 kelas × 1 mapel) yang jadi tanggung
    // jawabnya pada periode berjalan.
    $bolehLihatSemua = in_array(auth()->user()->role, ['admin', 'kurikulum', 'kepala_sekolah']);
    $selesai = $lembar->filter(fn ($l) => $l['header']?->isFinal())->count();
@endphp

<div class="space-y-6">
    {{-- Ringkasan singkat --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card p-4">
            <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Lembar Nilai</p>
            <p class="text-2xl font-extrabold text-slate-800 mt-1">{{ $lembar->count() }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Sudah Final</p>
            <p class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $selesai }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Belum Final</p>
            <p class="text-2xl font-extrabold text-amber-600 mt-1">{{ $lembar->count() - $selesai }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Periode</p>
            <p class="text-sm font-bold text-slate-800 mt-2">{{ $periode->labelSingkat() }}</p>
        </div>
    </div>

    <x-panel judul="Lembar Daftar Nilai"
             :deskripsi="$bolehLihatSemua
                ? 'Seluruh lembar daftar nilai pada periode berjalan.'
                : 'Kelas dan mata pelajaran yang Anda ampu pada periode berjalan.'"
             ikon="fa-table-list" rapat>
        <div class="overflow-x-auto">
            <table class="table-clean">
                <thead>
                    <tr>
                        <th class="w-10">No</th>
                        <th>Kelas</th>
                        <th>Mata Pelajaran</th>
                        @if($bolehLihatSemua)<th>Guru Pengampu</th>@endif
                        <th class="min-w-[180px]">Kemajuan Pengisian</th>
                        <th>Status</th>
                        <th class="th-aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lembar as $l)
                        <tr>
                            <td class="text-center text-slate-400">{{ $loop->iteration }}</td>
                            <td><x-kelas-badge :nama="$l['kelas']->nama_kelas" /></td>
                            <td class="font-medium text-slate-700">{{ $l['mapel']->nama_mapel }}</td>
                            @if($bolehLihatSemua)
                                <td class="text-slate-600">{{ $l['guru']->name ?? '-' }}</td>
                            @endif
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden min-w-[80px]">
                                        <div class="h-full rounded-full {{ $l['persen'] >= 100 ? 'bg-emerald-500' : ($l['persen'] > 0 ? 'bg-brand-500' : 'bg-slate-200') }}"
                                             style="width: {{ min(100, $l['persen']) }}%"></div>
                                    </div>
                                    <span class="text-xs text-slate-500 tabular-nums whitespace-nowrap">
                                        {{ $l['sudah_dinilai'] }}/{{ $l['total_siswa'] }}
                                    </span>
                                </div>
                            </td>
                            <td>
                                @if($l['header']?->isFinal())
                                    <span class="badge bg-emerald-50 text-emerald-700"><i class="fa-solid fa-lock mr-1"></i> Final</span>
                                @elseif($l['sudah_dinilai'] > 0)
                                    <span class="badge bg-amber-50 text-amber-700">Draft</span>
                                @else
                                    <span class="badge bg-slate-100 text-slate-500">Belum diisi</span>
                                @endif
                            </td>
                            <td class="td-aksi">
                                <a href="{{ route('nilai.form', ['kelas' => $l['kelas']->id, 'mapel' => $l['mapel']->id]) }}"
                                   class="btn-chip btn-chip-edit">
                                    <i class="fa-solid fa-pen-to-square"></i> Buka
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $bolehLihatSemua ? 7 : 6 }}">
                                <div class="empty-state">
                                    <i class="fa-solid fa-table-list text-3xl text-slate-300 mb-3 block"></i>
                                    Belum ada kelas & mata pelajaran yang dipetakan untuk Anda pada
                                    <b>{{ $periode->labelPeriode() }}</b>.<br>
                                    Hubungi Kurikulum untuk melengkapi <i>Pemetaan Guru Mengajar</i>.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-panel>
</div>
@endsection
