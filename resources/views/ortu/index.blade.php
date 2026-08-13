@extends('layouts.app')
@section('title', 'Portal Orang Tua')

@section('content')
<div class="space-y-6">
    <div>
        <p class="text-xl font-extrabold text-slate-800">Selamat datang, {{ auth()->user()->name }} 👋</p>
        <p class="text-sm text-slate-400">Pilih anak untuk melihat absensi dan riwayat pelanggaran.</p>
    </div>

    @if($anakList->isEmpty())
        <div class="card p-10 text-center">
            <p class="text-3xl mb-2">🧒</p>
            <p class="font-bold text-slate-700">Belum ada data anak yang ditautkan</p>
            <p class="text-sm text-slate-400 mt-1">Silakan hubungi Admin sekolah untuk menautkan akun Anda ke data siswa.</p>
        </div>
    @else
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($anakList as $anak)
            @php $r = $ringkasanPerAnak[$anak->id] ?? null; @endphp
            <a href="{{ route('ortu.show', $anak) }}" class="card p-5 hover:shadow-md hover:border-brand-200 transition block">
                <div class="flex items-center gap-3 mb-4">
                    <x-initial-avatar :nama="$anak->nama" size="w-11 h-11 text-base" />
                    <div class="min-w-0">
                        <p class="font-bold text-slate-800 truncate">{{ $anak->nama }}</p>
                        <div class="flex items-center gap-2 text-xs text-slate-400">
                            <span>{{ $anak->nis }}</span> &middot; <x-kelas-badge :nama="$anak->kelas->nama_kelas ?? '-'" />
                        </div>
                    </div>
                </div>
                @if($r)
                <div class="grid grid-cols-4 gap-2 text-center text-xs">
                    <div class="rounded-lg bg-amber-50 py-2">
                        <p class="font-bold text-amber-700">{{ $r['sakit'] }}</p><p class="text-amber-500">Sakit</p>
                    </div>
                    <div class="rounded-lg bg-sky-50 py-2">
                        <p class="font-bold text-sky-700">{{ $r['izin'] }}</p><p class="text-sky-500">Izin</p>
                    </div>
                    <div class="rounded-lg bg-rose-50 py-2">
                        <p class="font-bold text-rose-700">{{ $r['alfa'] }}</p><p class="text-rose-500">Alfa</p>
                    </div>
                    <div class="rounded-lg bg-violet-50 py-2">
                        <p class="font-bold text-violet-700">{{ $r['poin_aktif'] }}</p><p class="text-violet-500">Poin</p>
                    </div>
                </div>
                <p class="text-[11px] text-slate-400 mt-2">Rekap absensi bulan ini &middot; poin pelanggaran aktif</p>
                @endif
            </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
