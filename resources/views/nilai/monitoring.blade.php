@extends('layouts.app')
@section('title', 'Monitoring Input Nilai')

@section('content')
@php
    $gaya = [
        'final' => ['label' => 'Final', 'badge' => 'bg-emerald-50 text-emerald-700', 'angka' => 'text-emerald-600', 'ikon' => 'fa-lock'],
        'lengkap' => ['label' => 'Lengkap, belum final', 'badge' => 'bg-sky-50 text-sky-700', 'angka' => 'text-sky-600', 'ikon' => 'fa-circle-check'],
        'sebagian' => ['label' => 'Baru sebagian', 'badge' => 'bg-amber-50 text-amber-700', 'angka' => 'text-amber-600', 'ikon' => 'fa-hourglass-half'],
        'kosong' => ['label' => 'Belum diisi', 'badge' => 'bg-rose-50 text-rose-700', 'angka' => 'text-rose-600', 'ikon' => 'fa-circle-xmark'],
    ];
@endphp

<div class="space-y-6">

    {{-- ================= Ringkasan ================= --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="card p-4">
            <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Total Lembar</p>
            <p class="text-2xl font-extrabold text-slate-800 mt-1">{{ $ringkasan['total'] }}</p>
            <p class="text-[11px] text-slate-400 mt-1">kelas &times; mata pelajaran</p>
        </div>
        @foreach(['final', 'lengkap', 'sebagian', 'kosong'] as $kunci)
            <a href="{{ route('nilai.monitoring', array_filter(['tahun_ajaran_id' => $periode->id, 'kelas_id' => $filterKelas, 'status' => $kunci])) }}"
               class="card card-hover p-4 block">
                <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">{{ $gaya[$kunci]['label'] }}</p>
                <p class="text-2xl font-extrabold mt-1 {{ $gaya[$kunci]['angka'] }}">{{ $ringkasan[$kunci] }}</p>
                <p class="text-[11px] text-slate-400 mt-1"><i class="fa-solid {{ $gaya[$kunci]['ikon'] }} mr-1"></i> klik untuk menyaring</p>
            </a>
        @endforeach
    </div>

    {{-- ================= Penyaring ================= --}}
    <div class="card p-4 no-print">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="label">Periode</label>
                <select name="tahun_ajaran_id" class="select" onchange="this.form.submit()">
                    @foreach($daftarPeriode as $p)
                        <option value="{{ $p->id }}" @selected($p->id === $periode->id)>{{ $p->labelSingkat() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Kelas</label>
                <select name="kelas_id" class="select" onchange="this.form.submit()">
                    <option value="">Semua kelas</option>
                    @foreach($daftarKelas as $k)
                        <option value="{{ $k->id }}" @selected($k->id === $filterKelas)>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Status</label>
                <select name="status" class="select" onchange="this.form.submit()">
                    <option value="">Semua status</option>
                    @foreach($gaya as $kunci => $g)
                        <option value="{{ $kunci }}" @selected($filterStatus === $kunci)>{{ $g['label'] }}</option>
                    @endforeach
                </select>
            </div>
            @if($filterKelas || $filterStatus)
                <a href="{{ route('nilai.monitoring', ['tahun_ajaran_id' => $periode->id]) }}" class="btn-ghost">
                    <i class="fa-solid fa-xmark mr-1.5"></i> Hapus saringan
                </a>
            @endif
            <button type="button" onclick="cetakBagian('print-monitoring-nilai')" class="btn-outline">
                <i class="fa-solid fa-print mr-1.5"></i> Cetak / Export PDF
            </button>
        </form>
    </div>

    {{-- ================= Tabel ================= --}}
    <div class="card p-5 print-section" id="print-monitoring-nilai">
        <x-kop-surat />

        <div class="mb-4">
            <p class="font-extrabold text-slate-800 text-lg">Monitoring Input Nilai</p>
            <p class="text-sm text-slate-400">
                {{ $periode->labelPeriode() }}
                @if($filterKelas) &middot; Kelas {{ $daftarKelas->firstWhere('id', $filterKelas)?->nama_kelas }} @endif
                @if($filterStatus) &middot; Status: {{ $gaya[$filterStatus]['label'] ?? $filterStatus }} @endif
            </p>
        </div>

        <div class="overflow-x-auto -mx-5">
            <table class="table-clean">
                <thead>
                    <tr>
                        <th class="w-10">No</th>
                        <th>Kelas</th>
                        <th>Mata Pelajaran</th>
                        <th>Guru Pengampu</th>
                        <th class="min-w-[170px]">Kemajuan</th>
                        <th>Siap Final</th>
                        <th>Status</th>
                        <th class="th-aksi no-print">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($barisTampil as $b)
                        <tr>
                            <td class="text-center text-slate-400">{{ $loop->iteration }}</td>
                            <td><x-kelas-badge :nama="$b['kelas']->nama_kelas" /></td>
                            <td class="font-medium text-slate-700">{{ $b['mapel']->nama_mapel }}</td>
                            <td class="text-slate-600">{{ $b['guru']->name ?? '-' }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden min-w-[70px]">
                                        <div class="h-full rounded-full {{ $b['persen'] >= 100 ? 'bg-emerald-500' : ($b['persen'] > 0 ? 'bg-brand-500' : 'bg-slate-200') }}"
                                             style="width: {{ min(100, $b['persen']) }}%"></div>
                                    </div>
                                    <span class="text-xs text-slate-500 tabular-nums whitespace-nowrap">{{ $b['ada_nilai'] }}/{{ $b['total'] }}</span>
                                </div>
                            </td>
                            <td class="tabular-nums text-slate-600">{{ $b['lengkap'] }}/{{ $b['total'] }}</td>
                            <td>
                                <span class="badge {{ $gaya[$b['status']]['badge'] }}">
                                    <i class="fa-solid {{ $gaya[$b['status']]['ikon'] }} mr-1"></i> {{ $gaya[$b['status']]['label'] }}
                                </span>
                            </td>
                            <td class="td-aksi no-print">
                                <a href="{{ route('nilai.form', ['kelas' => $b['kelas']->id, 'mapel' => $b['mapel']->id]) }}"
                                   class="btn-chip btn-chip-edit"><i class="fa-solid fa-eye"></i> Lihat</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <i class="fa-solid fa-clipboard-list text-3xl text-slate-300 mb-3 block"></i>
                                    Tidak ada lembar daftar nilai yang cocok dengan saringan ini.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p class="mt-4 text-[11px] text-slate-500">
            <b>Kemajuan</b> = berapa siswa yang sudah punya nilai akhir. <b>Siap Final</b> = berapa siswa yang
            seluruh komponennya (Formatif/Sumatif Lingkup Materi, ASTS, dan Sumatif Akhir) sudah terisi lengkap.
            Lembar hanya dapat difinalisasi bila seluruh siswa sudah lengkap.
        </p>

        <x-blok-tanda-tangan jabatan="Kurikulum" />
    </div>
</div>
@endsection
