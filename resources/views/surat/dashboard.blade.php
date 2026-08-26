@extends('layouts.app')
@section('title', 'Ringkasan Surat')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-start flex-wrap gap-3">
        <div>
            <p class="text-xl font-extrabold text-slate-800">Dashboard Surat BK</p>
            <p class="text-sm text-slate-500">Ringkasan surat yang Anda buat{{ $pengaturanSekolahGlobal->nama_sekolah ? ' di ' . $pengaturanSekolahGlobal->nama_sekolah : '' }}.</p>
        </div>
        <a href="{{ route('surat.create') }}" class="btn-primary">+ Buat Surat Baru</a>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card p-5">
            <div class="w-11 h-11 rounded-full bg-emerald-50 flex items-center justify-center mb-3">
                <i class="fa-solid fa-envelope text-emerald-600"></i>
            </div>
            <p class="text-sm font-semibold text-slate-600">Total Surat</p>
            <p class="text-3xl font-extrabold text-slate-800 mt-1">{{ $ringkasan['total'] }}</p>
            <p class="text-xs text-slate-400 mt-1">Bulan ini: <span class="font-semibold text-slate-600">{{ $ringkasan['bulan_ini'] }}</span></p>
        </div>
        <div class="card p-5">
            <div class="w-11 h-11 rounded-full bg-violet-50 flex items-center justify-center mb-3">
                <i class="fa-solid fa-circle-check text-violet-600"></i>
            </div>
            <p class="text-sm font-semibold text-slate-600">Selesai</p>
            <p class="text-3xl font-extrabold text-slate-800 mt-1">{{ $ringkasan['selesai'] }}</p>
            <p class="text-xs text-slate-400 mt-1">Bulan ini: <span class="font-semibold text-slate-600">{{ $ringkasan['selesai_bulan_ini'] }}</span></p>
        </div>
        <div class="card p-5">
            <div class="w-11 h-11 rounded-full bg-blue-50 flex items-center justify-center mb-3">
                <i class="fa-solid fa-file-pen text-blue-600"></i>
            </div>
            <p class="text-sm font-semibold text-slate-600">Draft</p>
            <p class="text-3xl font-extrabold text-slate-800 mt-1">{{ $ringkasan['draft'] }}</p>
            <a href="{{ route('surat.index', ['status' => 'draft']) }}" class="text-xs text-brand-600 font-semibold mt-1 inline-block">Lihat &rarr;</a>
        </div>
        <div class="card p-5">
            <div class="w-11 h-11 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                <i class="fa-solid fa-box-archive text-slate-500"></i>
            </div>
            <p class="text-sm font-semibold text-slate-600">Diarsipkan</p>
            <p class="text-3xl font-extrabold text-slate-800 mt-1">{{ $ringkasan['diarsipkan'] }}</p>
            <a href="{{ route('surat.index', ['status' => 'diarsipkan']) }}" class="text-xs text-brand-600 font-semibold mt-1 inline-block">Lihat &rarr;</a>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="card p-5 lg:col-span-2">
            <p class="font-bold text-slate-800 mb-4 text-sm">Surat Dibuat (6 Bulan Terakhir)</p>
            @php
                $maxNilai = max(1, $statistik->pluck('jumlah')->max());
                $lebar = 600; $tinggi = 160; $n = $statistik->count();
                $jarakX = $n > 1 ? $lebar / ($n - 1) : $lebar;
                $titik = $statistik->values()->map(fn ($b, $i) => round($i * $jarakX, 1) . ',' . round($tinggi - ($b['jumlah'] / $maxNilai) * $tinggi, 1))->implode(' ');
            @endphp
            <svg viewBox="0 0 {{ $lebar }} {{ $tinggi + 20 }}" class="w-full" preserveAspectRatio="none" style="height:180px">
                <polyline points="{{ $titik }}" fill="none" stroke="#10b981" stroke-width="2.5" />
                @foreach($statistik->values() as $i => $b)
                    <circle cx="{{ round($i * $jarakX, 1) }}" cy="{{ round($tinggi - ($b['jumlah'] / $maxNilai) * $tinggi, 1) }}" r="3" fill="#10b981" />
                @endforeach
            </svg>
            <div class="flex justify-between text-xs text-slate-400 mt-1">
                @foreach($statistik as $b)<span>{{ $b['label'] }}</span>@endforeach
            </div>
        </div>

        <div class="card p-5">
            <div class="flex items-center justify-between mb-3">
                <p class="font-bold text-slate-800 text-sm">Surat Terbaru</p>
                <a href="{{ route('surat.index') }}" class="text-xs text-brand-600 font-semibold">Lihat Semua</a>
            </div>
            <div class="space-y-2">
                @forelse($suratTerbaru as $s)
                    <a href="{{ route('surat.show', $s) }}" class="flex items-center justify-between border border-slate-200 rounded-lg px-3 py-2 text-sm hover:bg-slate-50">
                        <div>
                            <p class="font-medium">{{ $s->jenisSurat->nama_jenis ?? '-' }}</p>
                            <p class="text-xs text-slate-400">{{ $s->siswa->nama ?? '-' }} &middot; {{ $s->nomor_surat ?: '-' }}</p>
                        </div>
                        <span class="text-xs text-slate-400 whitespace-nowrap">{{ $s->tanggal->translatedFormat('d M') }}</span>
                    </a>
                @empty
                    <p class="text-xs text-slate-400">Belum ada surat.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
