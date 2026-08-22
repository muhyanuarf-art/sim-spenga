@extends('layouts.app')
@section('title', 'Import Data Siswa')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="card p-6">
        <p class="font-bold text-slate-800 mb-1">Import Data Siswa dari Excel</p>
        <p class="text-sm text-slate-400 mb-5">
            Format kolom (baris pertama header): <code class="bg-slate-100 px-1.5 py-0.5 rounded">nis</code>,
            <code class="bg-slate-100 px-1.5 py-0.5 rounded">nisn</code>,
            <code class="bg-slate-100 px-1.5 py-0.5 rounded">nama</code>,
            <code class="bg-slate-100 px-1.5 py-0.5 rounded">jenis_kelamin</code> (L/P),
            <code class="bg-slate-100 px-1.5 py-0.5 rounded">kode_kelas</code>
        </p>

        <a href="{{ route('siswa.template') }}" class="inline-flex items-center gap-1.5 text-brand-600 hover:underline text-sm font-semibold mb-5">
            <i class="fa-solid fa-file-lines mr-1.5"></i> Download Template Excel
        </a>

        <form method="POST" action="{{ route('siswa.import') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="file" name="file" required accept=".xlsx,.xls,.csv" class="input">
            <div class="flex gap-3">
                <a href="{{ route('siswa.index') }}" class="btn-outline">Batal</a>
                <button type="submit" class="btn-primary">Upload & Import</button>
            </div>
        </form>
    </div>
</div>
@endsection
