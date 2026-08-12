@extends('layouts.app')
@section('title', 'Catat Kasus Baru')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="card p-6"
         x-data="{ jenisData: {{ $jenisList->map(fn($j) => ['id'=>$j->id,'nama'=>$j->nama,'kategori'=>$j->kategori,'poin'=>$j->poin_default])->values() }},
                   jenisId: '', nama: '', kategori: '', poin: '',
                   pilihJenis(id) {
                       const j = this.jenisData.find(x => x.id == id);
                       if (j) { this.nama = j.nama; this.kategori = j.kategori; this.poin = j.poin; }
                   } }">
        <p class="font-bold text-lg text-slate-800 mb-4">Catat Kasus / Pelanggaran Baru</p>

        <form method="POST" action="{{ route('bk.kasus.store') }}" class="space-y-3">
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
                <label class="block text-xs font-semibold text-slate-500 mb-1">Jenis Pelanggaran (dari master, opsional)</label>
                <select x-model="jenisId" @change="pilihJenis(jenisId)" name="jenis_pelanggaran_id" class="input">
                    <option value="">-- Pilih dari master / isi manual di bawah --</option>
                    @foreach($jenisList as $j)
                        <option value="{{ $j->id }}">{{ $j->nama }} ({{ $j->kategori }}, {{ $j->poin_default }} poin)</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Nama Pelanggaran</label>
                <input type="text" name="nama_pelanggaran" x-model="nama" required class="input">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Kategori</label>
                    <select name="kategori" x-model="kategori" required class="input">
                        <option value="">Pilih</option>
                        @foreach($rentangKategori as $kat => [$min,$max])
                            <option value="{{ $kat }}">{{ $kat }} ({{ $min }}-{{ $max }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Poin</label>
                    <input type="number" name="poin" x-model="poin" required min="1" max="100" class="input">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Kronologi</label>
                <textarea name="kronologi" required rows="3" class="input"></textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Bukti/Catatan Pendukung (opsional)</label>
                <textarea name="bukti_catatan" rows="2" class="input"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <a href="{{ route('bk.kasus.index') }}" class="btn-outline">Batal</a>
                <button type="submit" class="btn-primary bg-rose-600 hover:bg-rose-700">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
