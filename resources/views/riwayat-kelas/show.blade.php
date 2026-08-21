@extends('layouts.app')
@section('title', 'Riwayat Kelas — ' . $siswa->nama)

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <p class="font-bold text-slate-800 text-lg">{{ $siswa->nama }}</p>
            <p class="text-sm text-slate-500">NIS {{ $siswa->nis }} · Kelas saat ini: {{ $siswa->kelas->nama_kelas ?? '-' }}</p>
        </div>
        <a href="{{ route('siswa.index') }}" class="btn-outline">&larr; Kembali ke Data Siswa</a>
    </div>

    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-4">Riwayat Kelas</p>

        @forelse($riwayat as $i => $r)
        <div class="flex gap-4 pb-5 {{ !$loop->last ? 'border-l-2 border-brand-100 ml-4' : 'ml-4' }} relative">
            <div class="absolute -left-[1.05rem] top-0 w-8 h-8 rounded-full bg-brand-600 text-white text-xs font-bold flex items-center justify-center">
                {{ $i + 1 }}
            </div>
            <div class="pl-8 pt-0.5">
                <p class="font-semibold text-slate-800">
                    {{ $r->kelasAsal->nama_kelas ?? 'Belum tercatat' }}
                    <span class="text-slate-400">&rarr;</span>
                    {{ $r->kelas->nama_kelas ?? '-' }}
                </p>
                <p class="text-sm text-slate-500">
                    {{ $r->tahunAjaran?->labelPeriode() ?? 'Tahun Ajaran -' }}
                </p>
                @if($r->keterangan)
                    <p class="text-sm text-slate-600 mt-1">{{ $r->keterangan }}</p>
                @endif
                <p class="text-xs text-slate-400 mt-1">
                    Dicatat oleh {{ $r->dicatatOleh->name ?? '-' }} · {{ $r->created_at->translatedFormat('d M Y H:i') }}
                </p>
            </div>
        </div>
        @empty
        <p class="text-center text-slate-400 py-8">Belum ada riwayat kenaikan kelas untuk siswa ini.</p>
        @endforelse
    </div>
</div>
@endsection
