@extends('layouts.app')
@section('title', 'Import Mata Pelajaran')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="card p-6">
        <p class="font-bold text-slate-800 mb-1">Import Mata Pelajaran dari Excel</p>
        <p class="text-sm text-slate-400 mb-5">
            Format kolom (baris pertama header): <code class="bg-slate-100 px-1.5 py-0.5 rounded">kode</code>,
            <code class="bg-slate-100 px-1.5 py-0.5 rounded">nama_mapel</code>
        </p>

        <a href="{{ route('mapel.template') }}" class="inline-flex items-center gap-1.5 text-brand-600 hover:underline text-sm font-semibold mb-5">
            <i class="fa-solid fa-file-lines mr-1.5"></i> Download Template Excel
        </a>

        <form method="POST" action="{{ route('mapel.import') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="file" name="file" required accept=".xlsx,.xls,.csv" class="input">
            <div class="flex gap-3">
                <a href="{{ route('mapel.index') }}" class="btn-outline">Batal</a>
                <button type="submit" class="btn-primary">Upload & Import</button>
            </div>
        </form>
    </div>

    <x-hasil-import />
</div>
@endsection
