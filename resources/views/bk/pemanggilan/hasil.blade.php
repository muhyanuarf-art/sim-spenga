@extends('layouts.app')
@section('title', 'Isi Hasil Pertemuan')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <p class="text-lg font-extrabold text-slate-800">Isi Hasil Pertemuan</p>
        <a href="{{ route('bk.siswa.show', $pemanggilan->siswa_id) }}" class="btn-outline">&larr; Kembali</a>
    </div>

    <div class="card p-5">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Pemanggilan</p>
        <p class="font-bold text-slate-800">{{ $pemanggilan->siswa->nama ?? '-' }} &middot; {{ $pemanggilan->siswa->kelas->nama_kelas ?? '-' }}</p>
        <p class="text-sm text-slate-500 mt-1">Tanggal pemanggilan: {{ $pemanggilan->tanggal->translatedFormat('d F Y') }}</p>
        <p class="text-sm text-slate-500">Alasan: {{ $pemanggilan->alasan }}</p>
        @if($pemanggilan->surat)
            <a href="{{ route('surat.show', $pemanggilan->surat) }}" target="_blank" class="inline-flex items-center gap-1 text-xs text-brand-600 hover:underline mt-2">
                <i class="fa-solid fa-envelope mr-1"></i> Lihat Surat Panggilan ({{ $pemanggilan->surat->nomor_surat ?: 'belum ada nomor' }})
            </a>
        @endif
    </div>

    <form method="POST" action="{{ route('bk.pemanggilan.hasil.update', $pemanggilan) }}" x-data="{ hadir: '{{ old('ortu_hadir', $pemanggilan->ortu_hadir === null ? '1' : ($pemanggilan->ortu_hadir ? '1' : '0')) }}' }" class="card p-5 space-y-4">
        @csrf @method('PUT')

        <p class="font-bold text-slate-800 text-sm">Setelah pertemuan berlangsung, isi hasilnya di sini:</p>

        <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1">Orang Tua/Wali Hadir?</label>
            <select name="ortu_hadir" x-model="hadir" required class="input">
                <option value="1">Ya, hadir</option>
                <option value="0">Tidak hadir</option>
            </select>
        </div>
        <div x-show="hadir === '1'">
            <label class="block text-xs font-semibold text-slate-500 mb-1">Hasil Pertemuan</label>
            <textarea name="hasil_pertemuan" rows="3" class="input">{{ old('hasil_pertemuan', $pemanggilan->hasil_pertemuan) }}</textarea>
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1">Kesepakatan <span class="text-slate-400 font-normal">(opsional)</span></label>
            <textarea name="kesepakatan" rows="3" class="input">{{ old('kesepakatan', $pemanggilan->kesepakatan) }}</textarea>
        </div>

        <button type="submit" class="btn-primary h-[38px]">Simpan Hasil Pertemuan</button>
    </form>
</div>
@endsection
