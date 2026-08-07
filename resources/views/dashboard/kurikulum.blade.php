@extends('layouts.app')
@section('title', 'Dashboard Kurikulum')

@section('content')
<div class="space-y-6">
    @if(!$tahunAjaran)
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
            ⚠️ Belum ada Tahun Ajaran aktif. Silakan aktifkan di menu <b>Tahun Ajaran</b>.
        </div>
    @endif

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Jadwal Hari Ini</p>
            <p class="text-2xl font-extrabold text-slate-800">{{ $totalJadwalHariIni }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Jurnal Terisi</p>
            <p class="text-2xl font-extrabold text-slate-800">{{ $totalJurnalHariIni }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Total Guru</p>
            <p class="text-2xl font-extrabold text-slate-800">{{ $totalGuru }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Mapping Aktif</p>
            <p class="text-2xl font-extrabold text-slate-800">{{ $totalMappingKelas }}</p>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-4">
        <a href="{{ route('kurikulum.guru-mengajar.index') }}" class="card p-5 hover:shadow-md transition group">
            <p class="font-bold text-slate-800 group-hover:text-brand-600">👨‍🏫 Mapping Guru Mengajar</p>
            <p class="text-sm text-slate-400 mt-1">Atur guru mengajar mapel apa di kelas mana.</p>
        </a>
        <a href="{{ route('jadwal.index') }}" class="card p-5 hover:shadow-md transition group">
            <p class="font-bold text-slate-800 group-hover:text-brand-600">🗓️ Jadwal Pelajaran</p>
            <p class="text-sm text-slate-400 mt-1">Susun jadwal pelajaran manual atau import Excel.</p>
        </a>
        <a href="{{ route('rekap.index') }}" class="card p-5 hover:shadow-md transition group">
            <p class="font-bold text-slate-800 group-hover:text-brand-600">📈 Rekapitulasi</p>
            <p class="text-sm text-slate-400 mt-1">Pantau kepatuhan pengisian jurnal & absensi.</p>
        </a>
    </div>

    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-4">Jurnal Mengajar Terbaru Hari Ini</p>
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>Guru</th><th>Kelas</th><th>Mapel</th><th>Materi</th></tr></thead>
                <tbody>
                    @forelse($jurnalHariIni as $j)
                    <tr>
                        <td class="font-medium">{{ $j->guru->name }}</td>
                        <td>{{ $j->kelas->nama_kelas }}</td>
                        <td>{{ $j->mapel->nama_mapel }}</td>
                        <td class="text-slate-500">{{ \Illuminate\Support\Str::limit($j->materi, 60) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-slate-400 py-6">Belum ada jurnal yang diisi hari ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
