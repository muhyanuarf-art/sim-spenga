@extends('layouts.app')
@section('title', 'Surat — ' . ($surat->siswa->nama ?? '-'))

@section('content')
<div class="space-y-6">
    <div class="card p-5 no-print flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('surat.index') }}" class="btn-outline">&larr; Kembali ke Daftar Surat</a>
        <div class="flex gap-2">
            <a href="{{ route('surat.edit', $surat) }}" class="btn-outline"><i class="fa-solid fa-pen mr-1.5"></i> Edit</a>
            <button type="button" onclick="cetakBagian('print-surat')" class="btn-primary"><i class="fa-solid fa-print mr-1.5"></i> Cetak / Export PDF</button>
            <form method="POST" action="{{ route('surat.destroy', $surat) }}" onsubmit="return confirm('Hapus surat ini?')">
                @csrf @method('DELETE')
                <button class="btn-chip btn-chip-delete"><i class="fa-solid fa-trash mr-1.5"></i> Hapus</button>
            </form>
        </div>
    </div>

    <div class="card p-8 print-section max-w-3xl mx-auto" id="print-surat">
        <x-kop-surat />

        <div class="flex justify-between items-start mb-6 text-sm">
            <div>
                <p><span class="text-slate-500">Nomor</span> : {{ $surat->nomor_surat ?: '-' }}</p>
                <p><span class="text-slate-500">Perihal</span> : {{ $surat->jenisSurat->nama_jenis ?? '-' }}</p>
            </div>
            <p>{{ $surat->tanggal->translatedFormat('d F Y') }}</p>
        </div>

        <div class="mb-6 text-sm">
            <p>Kepada Yth.</p>
            <p class="font-semibold">Orang Tua/Wali dari {{ $surat->siswa->nama ?? '-' }}</p>
            <p class="text-slate-500">Kelas {{ $surat->siswa->kelas->nama_kelas ?? '-' }}</p>
        </div>

        <div class="text-sm leading-relaxed whitespace-pre-line mb-4">{{ $surat->isi }}</div>

        <x-blok-tanda-tangan
            :jabatan="$surat->dibuatOleh->roleLabel() ?? '-'"
            :nama="$surat->dibuatOleh->name ?? null"
            :nip="$surat->dibuatOleh->nip ?? null"
        />
    </div>

    @if($surat->keterangan)
        <div class="card p-5 no-print">
            <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Keterangan (internal, tidak ikut tercetak)</p>
            <p class="text-sm text-slate-600">{{ $surat->keterangan }}</p>
        </div>
    @endif
</div>
@endsection
