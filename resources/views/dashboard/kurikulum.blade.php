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
        <x-stat-card color="sky" icon="🗓️" label="Jadwal Hari Ini" :value="$totalJadwalHariIni" suffix="jam" />
        <x-stat-card color="emerald" icon="✅" label="Jurnal Terisi" :value="$totalJurnalHariIni" :suffix="'/ '.$totalJadwalHariIni" />
        <x-stat-card color="amber" icon="👨‍🏫" label="Total Guru" :value="$totalGuru" />
        <x-stat-card color="violet" icon="🔗" label="Mapping Aktif" :value="$totalMappingKelas" />
    </div>

    <div class="grid lg:grid-cols-3 gap-4">
        <a href="{{ route('kurikulum.guru-mengajar.index') }}" class="card p-5 hover:shadow-md hover:border-violet-200 transition group">
            <div class="w-10 h-10 rounded-xl bg-violet-500 text-white flex items-center justify-center text-lg mb-3 shadow-lg shadow-violet-500/30">👨‍🏫</div>
            <p class="font-bold text-slate-800 group-hover:text-violet-600">Mapping Guru Mengajar</p>
            <p class="text-sm text-slate-400 mt-1">Atur guru mengajar mapel apa di kelas mana.</p>
        </a>
        <a href="{{ route('jadwal.index') }}" class="card p-5 hover:shadow-md hover:border-sky-200 transition group">
            <div class="w-10 h-10 rounded-xl bg-sky-500 text-white flex items-center justify-center text-lg mb-3 shadow-lg shadow-sky-500/30">🗓️</div>
            <p class="font-bold text-slate-800 group-hover:text-sky-600">Jadwal Pelajaran</p>
            <p class="text-sm text-slate-400 mt-1">Susun jadwal pelajaran manual atau import Excel.</p>
        </a>
        <a href="{{ route('rekap.index') }}" class="card p-5 hover:shadow-md hover:border-emerald-200 transition group">
            <div class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center text-lg mb-3 shadow-lg shadow-emerald-500/30">📈</div>
            <p class="font-bold text-slate-800 group-hover:text-emerald-600">Rekapitulasi</p>
            <p class="text-sm text-slate-400 mt-1">Pantau kepatuhan pengisian jurnal & absensi.</p>
        </a>
    </div>

    <x-alfa-widget :data="$siswaAlfaHariIni" />

    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-4">Jurnal Mengajar Terbaru Hari Ini</p>
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
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
                    <tr><td colspan="4" class="text-center text-slate-400 py-6">Belum ada jurnal yang diisi hari ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
