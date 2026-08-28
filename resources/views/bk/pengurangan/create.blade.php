@extends('layouts.app')
@section('title', 'Catat Pengurangan Poin')

@section('aksi')
    <a href="{{ route('bk.pengurangan.index') }}" class="btn-outline">&larr; Kembali</a>
@endsection

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <x-bk-pilih-siswa rute="bk.pengurangan.create" :siswa="$siswaTerpilih" :hasil="$hasilCari" />

    @if($siswaTerpilih)
        <div class="card p-5">
            <p class="font-bold text-slate-800 mb-1 text-sm">2. Isi Pengurangan Poin</p>
            <p class="text-xs text-slate-400 mb-4">
                Poin aktif {{ $siswaTerpilih->nama }} saat ini:
                <b class="text-rose-600">{{ $ringkasan['poin_aktif'] }}</b> — itulah batas maksimal pengurangan.
            </p>

            @if($ringkasan['poin_aktif'] < 1)
                <p class="alert alert-info mb-0">
                    <i class="fa-solid fa-circle-check mt-0.5"></i>
                    <span>Siswa ini tidak punya poin aktif, jadi tidak ada yang bisa dikurangi.</span>
                </p>
            @else
                <form method="POST" action="{{ route('bk.pengurangan.store') }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="siswa_id" value="{{ $siswaTerpilih->id }}">

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Tanggal</label>
                            <input type="date" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}" required class="input">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Jumlah Pengurangan</label>
                            <input type="number" name="jumlah" value="{{ old('jumlah') }}" required min="1"
                                   max="{{ $ringkasan['poin_aktif'] }}" class="input">
                            @error('jumlah')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Alasan <span class="text-rose-500">*</span></label>
                        <textarea name="alasan" required rows="2" class="input"
                                  placeholder="Mis. Menunjukkan perubahan perilaku konsisten selama 2 minggu">{{ old('alasan') }}</textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Dasar/Rekomendasi (opsional)</label>
                        <textarea name="dasar_rekomendasi" rows="2" class="input">{{ old('dasar_rekomendasi') }}</textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Catatan (opsional)</label>
                        <textarea name="catatan" rows="2" class="input">{{ old('catatan') }}</textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <a href="{{ route('bk.pengurangan.index') }}" class="btn-outline">Batal</a>
                        <button type="submit" class="btn-primary bg-emerald-600 hover:bg-emerald-700">Simpan Pengurangan</button>
                    </div>
                </form>
            @endif
        </div>
    @endif
</div>
@endsection
