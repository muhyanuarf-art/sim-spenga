@extends('layouts.app')
@section('title', 'Data Tidak Ditemukan')

@section('content')
{{-- Tanpa view ini, pesan Indonesia dari abort(404, "...") di controller
     ditelan halaman "Not Found" bawaan Laravel dan pengguna terlempar
     keluar aplikasi tanpa tahu sebabnya. --}}
<div class="max-w-lg mx-auto card p-8 text-center space-y-3">
    <div class="text-4xl text-slate-300"><i class="fa-solid fa-magnifying-glass"></i></div>
    <p class="text-sm text-slate-600 leading-relaxed">
        {{ $exception->getMessage() ?: 'Data yang Anda cari tidak ditemukan. Mungkin sudah dihapus, atau tautannya sudah tidak berlaku.' }}
    </p>
    <p class="text-xs text-slate-400">
        Kalau Anda yakin datanya ada, periksa pemilih periode di kanan atas —
        data bisa jadi milik Tahun Ajaran atau Semester yang berbeda.
    </p>
    <div class="pt-2 flex justify-center gap-2">
        <a href="{{ url()->previous() }}" class="btn-outline">← Kembali</a>
        <a href="{{ route('dashboard') }}" class="btn-primary">Ke Dashboard</a>
    </div>
</div>
@endsection
