@extends('layouts.app')
@section('title', 'Catat Pelanggaran')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="card p-6"
         x-data="{ jenisData: {{ $jenisList->map(fn($j) => ['id'=>$j->id,'nama'=>$j->nama,'kategori'=>$j->kategori,'poin'=>$j->poin_default])->values() }},
                   jenisId: '', nama: '', kategori: '', poin: '',
                   pilihJenis(id) {
                       const j = this.jenisData.find(x => x.id == id);
                       if (j) { this.nama = j.nama; this.kategori = j.kategori; this.poin = j.poin; }
                       else { this.nama = ''; this.kategori = ''; this.poin = ''; }
                   } }">
        <p class="font-bold text-lg text-slate-800 mb-4">Catat Kasus / Pelanggaran Baru</p>

        <form method="POST" action="{{ route('bk.kasus.store') }}" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Siswa</label>
                <select name="siswa_id" required class="input">
                    <option value="">-- Pilih Siswa --</option>
                    @foreach($siswaList as $s)
                        <option value="{{ $s->id }}">{{ $s->nama }} — {{ $s->kelas->nama_kelas ?? '-' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Tanggal Kejadian</label>
                <input type="date" name="tanggal_kejadian" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required class="input">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Jenis Pelanggaran</label>
                <select x-model="jenisId" @change="pilihJenis(jenisId)" name="jenis_pelanggaran_id" required class="input">
                    <option value="">-- Pilih Jenis Pelanggaran --</option>
                    @foreach($jenisList as $j)
                        <option value="{{ $j->id }}">{{ $j->nama }} ({{ $j->kategori }}, {{ $j->poin_default }} poin)</option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-400 mt-1">Tidak menemukan jenisnya? Tambahkan dulu di menu <a href="{{ route('bk.jenis-pelanggaran.index') }}" class="underline">Data Pelanggaran (Master)</a>.</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Nama Pelanggaran</label>
                <input type="text" name="nama_pelanggaran" x-model="nama" required class="input">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Kategori <span class="text-slate-300 font-normal">(otomatis)</span></label>
                    <div class="input bg-slate-50 text-slate-500 flex items-center" x-text="kategori || '-'"></div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Poin <span class="text-slate-300 font-normal">(otomatis)</span></label>
                    <div class="input bg-slate-50 text-slate-500 flex items-center font-bold" x-text="poin ? poin + ' poin' : '-'"></div>
                </div>
            </div>
            <p class="text-xs text-slate-400 -mt-2">Kategori & poin ditentukan otomatis oleh sistem sesuai Jenis Pelanggaran yang dipilih dari master — tidak bisa diubah manual.</p>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Kronologi <span class="text-rose-500">*</span></label>
                <textarea name="kronologi" required minlength="10" rows="3" class="input" placeholder="Ceritakan kejadiannya secara ringkas & jelas (wajib diisi)..."></textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Catatan Pendukung (opsional)</label>
                <textarea name="bukti_catatan" rows="2" class="input" placeholder="Catatan tambahan, boleh dikosongkan"></textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Upload Bukti — Foto/PDF (opsional)</label>
                <input type="file" name="bukti_file" accept=".jpg,.jpeg,.png,.pdf" class="input">
                <p class="text-xs text-slate-400 mt-1">Format JPG/PNG/PDF, maksimal 5MB. Boleh dikosongkan.</p>
                @error('bukti_file')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <a href="{{ route('bk.kasus.index') }}" class="btn-outline">Batal</a>
                <button type="submit" class="btn-primary bg-rose-600 hover:bg-rose-700">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection

