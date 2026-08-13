@extends('layouts.app')
@section('title', 'Import Akun Orang Tua')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="card p-6">
        <p class="font-bold text-slate-800 mb-1">Import Akun Orang Tua dari Excel</p>
        <p class="text-sm text-slate-400 mb-5">
            Format kolom (baris pertama header): <code class="bg-slate-100 px-1.5 py-0.5 rounded">nis</code> —
            hanya perlu NIS siswa, sistem akan otomatis mencocokkannya dengan Data Siswa dan membuatkan
            1 akun orang tua per NIS dengan password default <code class="bg-slate-100 px-1.5 py-0.5 rounded">password</code>.
        </p>

        <a href="{{ route('orangtua-akun.template') }}" class="inline-flex items-center gap-1.5 text-brand-600 hover:underline text-sm font-semibold mb-5">
            📄 Download Template Excel
        </a>

        <form method="POST" action="{{ route('orangtua-akun.import') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="file" name="file" required accept=".xlsx,.xls,.csv" class="input">
            <div class="flex gap-3">
                <a href="{{ route('orangtua-akun.index') }}" class="btn-outline">Batal</a>
                <button type="submit" class="btn-primary">Upload & Buat Akun</button>
            </div>
        </form>

        <div class="mt-6 pt-5 border-t border-slate-100 text-sm text-slate-500 space-y-1">
            <p class="font-semibold text-slate-600">Catatan:</p>
            <p>• NIS yang tidak ditemukan di Data Siswa akan dilewati otomatis.</p>
            <p>• NIS yang sudah punya akun tidak akan ditimpa (password lama tetap aman).</p>
            <p>• Orang tua login di <code class="bg-slate-100 px-1 rounded">/orangtua/login</code> pakai NIS anak + password <code class="bg-slate-100 px-1 rounded">password</code>, lalu disarankan langsung ganti password.</p>
        </div>
    </div>
</div>
@endsection
