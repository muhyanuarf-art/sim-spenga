@extends('layouts.app')
@section('title', 'Edit Surat')

@section('deskripsi', 'Surat untuk ' . ($surat->siswa->nama ?? '-'))

@section('aksi')
    <a href="{{ route('surat.show', $surat) }}" class="btn-outline">&larr; Kembali</a>
@endsection

@section('content')
@php
    $tipe = $surat->jenisSurat->tipe_formulir ?? 'bebas';
    $f = $surat->data_formulir ?? [];
@endphp
<div class="max-w-3xl mx-auto space-y-6">
    <form method="POST" action="{{ route('surat.update', $surat) }}" class="card p-5 space-y-4">
        @csrf @method('PUT')

        <div class="text-sm text-slate-500">
            Jenis Surat: <span class="font-semibold text-slate-700">{{ $surat->jenisSurat->nama_jenis ?? '-' }}</span> &middot;
            Siswa: <span class="font-semibold text-slate-700">{{ $surat->siswa->nama ?? '-' }}</span> ({{ $surat->siswa->kelas->nama_kelas ?? '-' }})
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Nomor Urut Surat</label>
                <input type="text" name="nomor_urut" value="{{ old('nomor_urut', $surat->nomor_urut) }}" required class="input">
                <p class="text-xs text-slate-400 mt-1">Nomor lengkap saat ini: <b>{{ $surat->nomor_surat }}</b></p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Tanggal Surat</label>
                <input type="date" name="tanggal" value="{{ old('tanggal', $surat->tanggal->toDateString()) }}" required class="input">
            </div>
        </div>

        @if($tipe === 'bebas')
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Tanggal Acara/Pemanggilan</label>
                    <input type="date" name="tanggal_acara" value="{{ old('tanggal_acara', optional($surat->tanggal_acara)->toDateString()) }}" class="input">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Waktu Acara</label>
                    <input type="time" name="waktu_acara" value="{{ old('waktu_acara', $surat->waktu_acara) }}" class="input">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Isi Surat</label>
                <textarea name="isi" rows="10" required class="input font-mono text-sm">{{ old('isi', $surat->isi) }}</textarea>
            </div>

        @elseif($tipe === 'izin_meninggalkan_pelajaran')
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Alamat</label>
                    <input type="text" name="alamat" value="{{ old('alamat', $f['alamat'] ?? '') }}" class="input">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Diberi Ijin Meninggalkan Pelajaran Mulai Jam Ke</label>
                    <input type="text" name="jam_ke" value="{{ old('jam_ke', $f['jam_ke'] ?? '') }}" class="input">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Keperluan</label>
                <textarea name="keperluan" rows="2" required class="input">{{ old('keperluan', $f['keperluan'] ?? '') }}</textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Keterangan Lain</label>
                <textarea name="keterangan_lain" rows="2" class="input">{{ old('keterangan_lain', $f['keterangan_lain'] ?? '') }}</textarea>
            </div>

        @elseif($tipe === 'keterangan_terlambat')
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Alamat</label>
                    <input type="text" name="alamat" value="{{ old('alamat', $f['alamat'] ?? '') }}" class="input">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Terlambat</label>
                    <input type="text" name="terlambat" value="{{ old('terlambat', $f['terlambat'] ?? '') }}" required class="input">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Alasan Terlambat</label>
                <textarea name="alasan_terlambat" rows="2" required class="input">{{ old('alasan_terlambat', $f['alasan_terlambat'] ?? '') }}</textarea>
            </div>

        @elseif($tipe === 'pernyataan_pelanggaran')
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Pelanggaran Ke</label>
                <input type="number" name="pelanggaran_ke" value="{{ old('pelanggaran_ke', $f['pelanggaran_ke'] ?? 1) }}" min="1" required class="input sm:w-32">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Pelanggaran Disiplin Sekolah Berupa</label>
                <textarea name="pelanggaran" rows="3" required class="input">{{ old('pelanggaran', $f['pelanggaran'] ?? '') }}</textarea>
            </div>
        @endif

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
