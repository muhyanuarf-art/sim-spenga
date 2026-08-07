@extends('layouts.app')
@section('title', 'Import Data Kelas')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="card p-6">
        <p class="font-bold text-slate-800 mb-1">Import Data Kelas dari Excel</p>
        <p class="text-sm text-slate-400 mb-5">
            Format kolom (baris pertama header): <code class="bg-slate-100 px-1.5 py-0.5 rounded">nama_kelas</code>,
            <code class="bg-slate-100 px-1.5 py-0.5 rounded">tingkat</code> (7/8/9),
            <code class="bg-slate-100 px-1.5 py-0.5 rounded">nip_wali_kelas</code> (opsional)
        </p>

        <a href="{{ route('kelas.template') }}" class="inline-flex items-center gap-1.5 text-brand-600 hover:underline text-sm font-semibold mb-5">
            📄 Download Template Excel
        </a>

        <form method="POST" action="{{ route('kelas.import') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="file" name="file" required accept=".xlsx,.xls,.csv" class="input">
            <div class="flex gap-3">
                <a href="{{ route('kelas.index') }}" class="btn-outline">Batal</a>
                <button type="submit" class="btn-primary">Upload & Import</button>
            </div>
        </form>
    </div>
</div>
@endsection
