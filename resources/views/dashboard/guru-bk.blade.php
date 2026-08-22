@extends('layouts.app')
@section('title', 'Dashboard Guru BK')

@section('content')
<div class="space-y-6">
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-violet-600 via-violet-600 to-indigo-500 text-white px-5 py-4 shadow-lg shadow-violet-500/20">
        <div class="relative z-10">
            <p class="font-bold flex items-center gap-2"><i class="fa-solid fa-compass mr-1.5"></i> Dashboard Guru BK</p>
            <p class="text-sm text-white/80">
                Memantau kehadiran siswa di {{ $kelasBk->count() }} kelas yang di-mapping-kan kepada Anda.
            </p>
        </div>
        <div class="absolute -right-6 -bottom-10 w-40 h-40 rounded-full bg-white/10 blur-2xl"></div>
    </div>

    @if($kelasBk->isEmpty())
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
            <i class="fa-solid fa-triangle-exclamation mr-1.5"></i> Anda belum di-mapping ke kelas manapun. Hubungi Kurikulum/Admin untuk diatur lewat menu
            <b>Mapping Guru BK</b>.
        </div>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($rekapPerKelasBk as $r)
            <a href="{{ route('walikelas.absensi-bulanan', $r['kelas']) }}"
               class="relative overflow-hidden rounded-2xl border p-4 transition block
                    {{ $r['alfa_hari_ini'] > 0 ? 'border-rose-200 bg-gradient-to-br from-rose-50 to-white hover:shadow-md' : 'border-emerald-100 bg-gradient-to-br from-emerald-50 to-white hover:shadow-md' }}">
                <p class="font-bold text-slate-800">Kelas {{ $r['kelas']->nama_kelas }}</p>
                <p class="text-xs text-slate-400 mb-2">{{ $r['total_siswa'] }} siswa</p>
                @if($r['alfa_hari_ini'] > 0)
                    <span class="badge bg-rose-100 text-rose-700"><i class="fa-solid fa-flag mr-1.5"></i> {{ $r['alfa_hari_ini'] }} Alfa hari ini</span>
                @else
                    <span class="badge bg-emerald-100 text-emerald-700"><i class="fa-solid fa-circle-check mr-1.5"></i> Aman hari ini</span>
                @endif
            </a>
            @endforeach
        </div>

        <x-alfa-widget :data="$siswaAlfaHariIni" title="Siswa Alfa Hari Ini — Kelas Mapping Anda" />

        <div class="card p-5">
            <p class="font-bold text-slate-800 mb-1">Menu Monitoring</p>
            <p class="text-sm text-slate-400 mb-4">Pantau lebih detail per kelas lewat menu berikut (bisa ganti kelas di dalamnya).</p>
            <div class="grid sm:grid-cols-3 gap-3">
                <a href="{{ route('walikelas.absensi-bulanan') }}" class="card p-4 hover:shadow-md hover:border-violet-200 transition group">
                    <div class="w-9 h-9 rounded-xl bg-violet-500 text-white flex items-center justify-center text-lg mb-2 shadow-lg shadow-violet-500/30"><i class="fa-solid fa-calendar-days"></i></div>
                    <p class="font-semibold text-slate-800 group-hover:text-violet-600 text-sm">Rekap Absensi Bulanan</p>
                </a>
                <a href="{{ route('walikelas.jurnal-kelas') }}" class="card p-4 hover:shadow-md hover:border-sky-200 transition group">
                    <div class="w-9 h-9 rounded-xl bg-sky-500 text-white flex items-center justify-center text-lg mb-2 shadow-lg shadow-sky-500/30"><i class="fa-solid fa-book"></i></div>
                    <p class="font-semibold text-slate-800 group-hover:text-sky-600 text-sm">Jurnal Kelas</p>
                </a>
                <a href="{{ route('notifikasi-wa.index') }}" class="card p-4 hover:shadow-md hover:border-emerald-200 transition group">
                    <div class="w-9 h-9 rounded-xl bg-emerald-500 text-white flex items-center justify-center text-lg mb-2 shadow-lg shadow-emerald-500/30"><i class="fa-solid fa-mobile-screen"></i></div>
                    <p class="font-semibold text-slate-800 group-hover:text-emerald-600 text-sm">Status WhatsApp Ortu</p>
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
