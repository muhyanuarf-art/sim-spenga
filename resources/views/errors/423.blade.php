@extends('layouts.app')
@section('title', 'Periode Terkunci')

@section('content')
<div class="max-w-lg mx-auto card p-8 text-center space-y-3">
    <div class="text-4xl text-slate-300"><i class="fa-solid fa-lock"></i></div>
    <p class="text-sm text-slate-500 leading-relaxed">
        {{ $exception->getMessage() ?: 'Periode akademik ini sudah ditutup dan terkunci. Data hanya dapat dilihat, tidak dapat diubah.' }}
    </p>
    <p class="text-xs text-slate-400">
        Data periode ini hanya dapat dilihat. Hubungi Admin jika Anda yakin perlu membuka kunci periode ini.
    </p>
    <div class="pt-2">
        <a href="{{ url()->previous() }}" class="btn-outline">← Kembali</a>
    </div>
</div>
@endsection
