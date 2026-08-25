@extends('layouts.app')
@section('title', 'Edit Surat')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <p class="text-lg font-extrabold text-slate-800">Edit Surat — {{ $surat->siswa->nama ?? '-' }}</p>
        <a href="{{ route('surat.show', $surat) }}" class="btn-outline">&larr; Kembali</a>
    </div>

    <form method="POST" action="{{ route('surat.update', $surat) }}" class="card p-5 space-y-4">
        @csrf @method('PUT')

        <div class="text-sm text-slate-500">
            Jenis Surat: <span class="font-semibold text-slate-700">{{ $surat->jenisSurat->nama_jenis ?? '-' }}</span> &middot;
            Siswa: <span class="font-semibold text-slate-700">{{ $surat->siswa->nama ?? '-' }}</span> ({{ $surat->siswa->kelas->nama_kelas ?? '-' }})
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Nomor Surat <span class="text-slate-400 font-normal">(otomatis saat dibuat, bisa dikoreksi di sini bila perlu)</span></label>
                <input type="text" name="nomor_surat" value="{{ old('nomor_surat', $surat->nomor_surat) }}" class="input">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Tanggal Surat Dibuat</label>
                <input type="date" name="tanggal" value="{{ old('tanggal', $surat->tanggal->toDateString()) }}" required class="input">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Tanggal Acara/Pemanggilan</label>
                <input type="date" name="tanggal_acara" value="{{ old('tanggal_acara', optional($surat->tanggal_acara)->toDateString()) }}" class="input">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Waktu Acara</label>
                <input type="time" name="waktu_acara" value="{{ old('waktu_acara', $surat->waktu_acara) }}" class="input">
            </div>
        </div>
        <p class="text-xs text-slate-400 -mt-2">Perubahan tanggal/waktu di sini TIDAK otomatis mengubah teks di kotak Isi Surat di bawah — kalau perlu, sesuaikan juga teksnya secara manual.</p>

        <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1">Isi Surat</label>
            <textarea name="isi" rows="10" required class="input font-mono text-sm">{{ old('isi', $surat->isi) }}</textarea>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1">Keterangan <span class="text-slate-400 font-normal">(opsional, internal)</span></label>
            <input type="text" name="keterangan" value="{{ old('keterangan', $surat->keterangan) }}" class="input">
        </div>

        <div class="flex gap-2">
            <button type="submit" class="btn-primary h-[38px]">Simpan Perubahan</button>
            <a href="{{ route('surat.show', $surat) }}" class="btn-outline h-[38px] flex items-center">Batal</a>
        </div>
    </form>
</div>
@endsection
