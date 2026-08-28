@extends('layouts.app')
@section('title', 'Belum Siap Dipakai')

@section('content')
{{-- Dipakai halaman yang butuh Tahun Ajaran aktif tapi belum ada satu pun
     yang diaktifkan admin. --}}
<div class="max-w-lg mx-auto card p-8 text-center space-y-3">
    <div class="text-4xl text-amber-300"><i class="fa-solid fa-calendar-xmark"></i></div>
    <p class="text-sm text-slate-600 leading-relaxed">
        {{ $exception->getMessage() ?: 'Halaman ini belum bisa dipakai karena ada syarat yang belum terpenuhi.' }}
    </p>
    <div class="pt-2 flex justify-center gap-2">
        <a href="{{ url()->previous() }}" class="btn-outline">← Kembali</a>
        @if(\App\Support\Navigasi::bolehAkses('tahun-ajaran.index', auth()->user()))
            <a href="{{ route('tahun-ajaran.index') }}" class="btn-primary">Buka Tahun Ajaran</a>
        @else
            <a href="{{ route('dashboard') }}" class="btn-primary">Ke Dashboard</a>
        @endif
    </div>
</div>
@endsection
