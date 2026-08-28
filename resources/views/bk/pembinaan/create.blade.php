@extends('layouts.app')
@section('title', 'Catat Pembinaan')

@section('aksi')
    <a href="{{ route('bk.pembinaan.index') }}" class="btn-outline">&larr; Kembali</a>
@endsection

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <x-bk-pilih-siswa rute="bk.pembinaan.create" :siswa="$siswaTerpilih" :hasil="$hasilCari" />

    @if($siswaTerpilih)
        <div class="card p-5" x-data="{ status: 'Pembinaan' }">
            <p class="font-bold text-slate-800 mb-1 text-sm">2. Isi Catatan Pembinaan</p>
            <p class="text-xs text-slate-400 mb-4">
                Tahap ditentukan otomatis oleh sistem dari poin aktif siswa saat ini
                ({{ $ringkasan['poin_aktif'] }} poin):
                <b class="text-violet-600">Tahap {{ $ringkasan['rekomendasi_tahap'] ?? 1 }}</b>
            </p>

            <form method="POST" action="{{ route('bk.pembinaan.store') }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <input type="hidden" name="siswa_id" value="{{ $siswaTerpilih->id }}">

                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Terkait Kasus (opsional)</label>
                    <select name="kasus_siswa_id" class="input">
                        <option value="">-- Tidak terkait kasus tertentu --</option>
                        @foreach($kasusAktifTerbuka as $k)
                            <option value="{{ $k->id }}" @selected(old('kasus_siswa_id') == $k->id)>
                                {{ $k->tanggal_kejadian->format('d/m/Y') }} — {{ $k->nama_pelanggaran }} (+{{ $k->poin }})
                            </option>
                        @endforeach
                    </select>
                    @if($kasusAktifTerbuka->isEmpty())
                        <p class="text-xs text-slate-400 mt-1">Siswa ini tidak punya kasus yang masih terbuka.</p>
                    @else
                        <p class="text-xs text-slate-400 mt-1">Bila dipilih, kasus tersebut otomatis ikut berstatus sesuai pembinaan ini.</p>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Tanggal</label>
                        <input type="date" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}" required class="input">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Tahap <span class="text-slate-300 font-normal">(otomatis)</span></label>
                        <div class="input bg-slate-50 text-slate-500 flex items-center font-bold">
                            Tahap {{ $ringkasan['rekomendasi_tahap'] ?? 1 }}
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Jenis Pembinaan</label>
                    <select name="jenis_pembinaan" required class="input">
                        {{-- Daftarnya dari model supaya selalu sama dengan
                             enum di database & aturan validasinya. --}}
                        @foreach(\App\Models\PembinaanSiswa::JENIS_LIST as $jp)
                            <option value="{{ $jp }}" @selected(old('jenis_pembinaan') === $jp)>{{ $jp }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Catatan BK <span class="text-rose-500">*</span></label>
                    <textarea name="catatan_bk" required rows="3" class="input" placeholder="Apa yang dibicarakan/dilakukan dalam pembinaan ini...">{{ old('catatan_bk') }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Status</label>
                    <select name="status" x-model="status" required class="input">
                        <option value="Pembinaan">Belum Selesai — masih berjalan</option>
                        <option value="Selesai">Selesai</option>
                    </select>
                    <p class="text-xs text-slate-400 mt-1">Statusnya bisa diubah kapan saja lewat tombol di daftar Pembinaan.</p>
                </div>

                <div x-show="status === 'Selesai'" x-cloak>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Hasil Pembinaan</label>
                    <textarea name="hasil_pembinaan" rows="2" class="input">{{ old('hasil_pembinaan') }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Upload Bukti — Foto/PDF (opsional)</label>
                    <input type="file" name="bukti_file" accept=".jpg,.jpeg,.png,.pdf" class="input">
                    <p class="text-xs text-slate-400 mt-1">Format JPG/PNG/PDF, maksimal 5MB. Boleh dikosongkan.</p>
                    @error('bukti_file')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Tanggal Evaluasi Berikutnya (opsional)</label>
                    <input type="date" name="tanggal_evaluasi_berikutnya" value="{{ old('tanggal_evaluasi_berikutnya') }}" class="input">
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <a href="{{ route('bk.pembinaan.index') }}" class="btn-outline">Batal</a>
                    <button type="submit" class="btn-primary">Simpan Pembinaan</button>
                </div>
            </form>
        </div>
    @endif
</div>
@endsection
