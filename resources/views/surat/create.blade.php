@extends('layouts.app')
@section('title', 'Buat Surat')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <p class="text-lg font-extrabold text-slate-800">Buat Surat</p>
        <a href="{{ route('surat.index') }}" class="btn-outline">&larr; Kembali</a>
    </div>

    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-3 text-sm">1. Pilih Jenis Surat</p>
        <form method="GET">
            <select name="jenis_surat_id" class="input" onchange="this.form.submit()">
                <option value="">— Pilih jenis surat —</option>
                @foreach($jenisSuratList as $j)
                    <option value="{{ $j->id }}" {{ $jenisSurat && $jenisSurat->id === $j->id ? 'selected' : '' }}>{{ $j->nama_jenis }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @if($jenisSurat)
    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-3 text-sm">2. Cari &amp; Pilih Siswa</p>
        <form method="GET" class="flex gap-2 mb-3">
            <input type="hidden" name="jenis_surat_id" value="{{ $jenisSurat->id }}">
            <input type="text" name="cari" value="{{ request('cari') }}" placeholder="Cari nama / NIS siswa..." class="input flex-1">
            <button type="submit" class="btn-outline">Cari</button>
        </form>

        @if($siswaTerpilih)
            <div class="flex items-center justify-between bg-brand-50/60 border border-brand-100 rounded-lg px-3 py-2">
                <div>
                    <p class="font-semibold text-sm">{{ $siswaTerpilih->nama }}</p>
                    <p class="text-xs text-slate-400">{{ $siswaTerpilih->nis }} &middot; {{ $siswaTerpilih->kelas->nama_kelas ?? '-' }}</p>
                </div>
                <a href="{{ route('surat.create', ['jenis_surat_id' => $jenisSurat->id]) }}" class="text-xs text-red-500 font-semibold">Ganti siswa</a>
            </div>
        @elseif(request()->filled('cari'))
            <div class="border border-slate-200 rounded-lg divide-y divide-slate-100">
                @forelse($hasilCari as $siswa)
                    <a href="{{ route('surat.create', ['jenis_surat_id' => $jenisSurat->id, 'siswa_id' => $siswa->id]) }}"
                       class="flex items-center justify-between px-3 py-2 hover:bg-slate-50">
                        <div>
                            <p class="font-semibold text-sm">{{ $siswa->nama }}</p>
                            <p class="text-xs text-slate-400">{{ $siswa->nis }} &middot; {{ $siswa->kelas->nama_kelas ?? '-' }}</p>
                        </div>
                        <span class="text-brand-600 text-xs font-semibold">Pilih</span>
                    </a>
                @empty
                    <p class="text-xs text-slate-400 px-3 py-3">Tidak ada siswa yang cocok.</p>
                @endforelse
            </div>
        @endif
    </div>
    @endif

    @if($jenisSurat && $siswaTerpilih)
    <form method="POST" action="{{ route('surat.store') }}" class="card p-5 space-y-4">
        @csrf
        <input type="hidden" name="jenis_surat_id" value="{{ $jenisSurat->id }}">
        <input type="hidden" name="siswa_id" value="{{ $siswaTerpilih->id }}">

        <p class="font-bold text-slate-800 text-sm">3. Lengkapi &amp; Simpan</p>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Nomor Surat <span class="text-slate-400 font-normal">(opsional)</span></label>
                <input type="text" name="nomor_surat" value="{{ old('nomor_surat') }}" class="input">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Tanggal</label>
                <input type="date" name="tanggal" value="{{ old('tanggal', $tanggal) }}" required class="input"
                       onchange="location.href = '{{ route('surat.create', ['jenis_surat_id' => $jenisSurat->id, 'siswa_id' => $siswaTerpilih->id]) }}&tanggal=' + this.value">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1">Isi Surat <span class="text-slate-400 font-normal">(otomatis digabung dari template, boleh diedit)</span></label>
            <textarea name="isi" rows="10" required class="input font-mono text-sm">{{ old('isi', $isiGabungan) }}</textarea>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1">Keterangan <span class="text-slate-400 font-normal">(opsional, internal — tidak ikut tercetak)</span></label>
            <input type="text" name="keterangan" value="{{ old('keterangan') }}" class="input">
        </div>

        <button type="submit" class="btn-primary h-[38px]">Simpan Surat</button>
    </form>
    @endif
</div>
@endsection
