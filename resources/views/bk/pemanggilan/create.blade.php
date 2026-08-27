@extends('layouts.app')
@section('title', 'Catat Pemanggilan Orang Tua')

@section('aksi')
    <a href="{{ route('bk.pemanggilan.index') }}" class="btn-outline">&larr; Kembali</a>
@endsection

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-3 text-sm">1. Cari &amp; Pilih Siswa</p>
        <form method="GET" class="flex gap-2 mb-3">
            <input type="text" name="cari" value="{{ request('cari') }}" placeholder="Cari nama / NIS siswa..." class="input flex-1">
            <button type="submit" class="btn-outline">Cari</button>
        </form>

        @if($siswaTerpilih)
            <div class="flex items-center justify-between bg-brand-50/60 border border-brand-100 rounded-lg px-3 py-2">
                <div>
                    <p class="font-semibold text-sm">{{ $siswaTerpilih->nama }}</p>
                    <p class="text-xs text-slate-400">{{ $siswaTerpilih->nis }} &middot; {{ $siswaTerpilih->kelas->nama_kelas ?? '-' }}</p>
                </div>
                <a href="{{ route('bk.pemanggilan.create') }}" class="text-xs text-red-500 font-semibold">Ganti siswa</a>
            </div>
        @elseif(request()->filled('cari'))
            <div class="border border-slate-200 rounded-lg divide-y divide-slate-100">
                @forelse($hasilCari as $siswa)
                    <a href="{{ route('bk.pemanggilan.create', ['siswa_id' => $siswa->id]) }}"
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

    @if($siswaTerpilih)
    <form method="POST" action="{{ route('bk.pemanggilan.store') }}" x-data="{ pilihanSurat: '{{ old('pilihan_surat', $suratPanggilanList->isEmpty() ? 'buat_baru' : 'tidak_ada') }}' }" class="space-y-6">
        @csrf
        <input type="hidden" name="siswa_id" value="{{ $siswaTerpilih->id }}">
        <input type="hidden" name="tanggal" value="{{ old('tanggal', $tanggal) }}">

        <div class="card p-5 space-y-3">
            <p class="font-bold text-slate-800 text-sm">2. Surat Panggilan</p>
            <p class="text-xs text-slate-400 -mt-2">Pilih salah satu — semuanya diselesaikan dalam 1x Simpan, tidak perlu buka halaman lain.</p>

            <div class="space-y-2">
                <label class="flex items-start gap-2.5 p-3 rounded-lg border cursor-pointer" :class="pilihanSurat === 'tidak_ada' ? 'border-brand-300 bg-brand-50/40' : 'border-slate-200'">
                    <input type="radio" name="pilihan_surat" value="tidak_ada" x-model="pilihanSurat" class="mt-0.5">
                    <span class="text-sm flex-1">
                        <span class="font-semibold text-slate-700">Tanpa surat</span>
                        <span class="block text-xs text-slate-400">Cukup catat pemanggilannya saja, tidak ada surat resmi yang dikaitkan.</span>
                        <div x-show="pilihanSurat === 'tidak_ada'" x-cloak class="mt-2">
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Alasan Pemanggilan</label>
                            <textarea name="alasan" rows="2" class="input">{{ old('alasan') }}</textarea>
                        </div>
                    </span>
                </label>

                @if($suratPanggilanList->isNotEmpty())
                <label class="flex items-start gap-2.5 p-3 rounded-lg border cursor-pointer" :class="pilihanSurat === 'pakai_yang_sudah_ada' ? 'border-brand-300 bg-brand-50/40' : 'border-slate-200'">
                    <input type="radio" name="pilihan_surat" value="pakai_yang_sudah_ada" x-model="pilihanSurat" class="mt-0.5">
                    <span class="text-sm flex-1">
                        <span class="font-semibold text-slate-700">Pakai surat yang sudah ada</span>
                        <select name="surat_id" class="input mt-2" x-show="pilihanSurat === 'pakai_yang_sudah_ada'" x-cloak>
                            @foreach($suratPanggilanList as $s)
                                <option value="{{ $s->id }}">{{ $s->nomor_surat ?: '(belum ada nomor)' }} &middot; {{ $s->tanggal->translatedFormat('d M Y') }}</option>
                            @endforeach
                        </select>
                    </span>
                </label>
                @endif

                <label class="flex items-start gap-2.5 p-3 rounded-lg border cursor-pointer" :class="pilihanSurat === 'buat_baru' ? 'border-brand-300 bg-brand-50/40' : 'border-slate-200'">
                    <input type="radio" name="pilihan_surat" value="buat_baru" x-model="pilihanSurat" class="mt-0.5" {{ !$jenisSuratPanggilan ? 'disabled' : '' }}>
                    <span class="text-sm">
                        <span class="font-semibold text-slate-700">Buat surat baru sekaligus</span>
                        <span class="block text-xs text-slate-400">
                            @if($jenisSuratPanggilan)
                                Surat "{{ $jenisSuratPanggilan->nama_jenis }}" dibuat otomatis bersamaan, langsung bernomor & bisa dicetak dari Manajemen Surat. Isi surat ini juga dipakai sebagai alasan pemanggilan.
                            @else
                                Belum ada Jenis Surat "Panggilan" di Manajemen Surat — buat dulu di sana sebelum bisa pakai opsi ini.
                            @endif
                        </span>
                    </span>
                </label>
            </div>

            @if($jenisSuratPanggilan)
            <div x-show="pilihanSurat === 'buat_baru'" x-cloak class="space-y-3 border-t border-slate-100 pt-4">
                <input type="hidden" name="jenis_surat_id" value="{{ $jenisSuratPanggilan->id }}">
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Tanggal Acara/Pemanggilan</label>
                        <input type="date" name="tanggal_acara" value="{{ old('tanggal_acara', $tanggalAcara) }}" class="input"
                               onchange="location.href = '{{ route('bk.pemanggilan.create', ['siswa_id' => $siswaTerpilih->id]) }}&tanggal_acara=' + this.value + '&waktu_acara={{ $waktuAcara }}'">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Waktu Acara</label>
                        <input type="time" name="waktu_acara" value="{{ old('waktu_acara', $waktuAcara) }}" class="input"
                               onchange="location.href = '{{ route('bk.pemanggilan.create', ['siswa_id' => $siswaTerpilih->id]) }}&tanggal_acara={{ $tanggalAcara }}&waktu_acara=' + this.value">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Nomor Urut Surat <span class="text-red-500">*</span></label>
                    <input type="text" name="nomor_urut" value="{{ old('nomor_urut') }}" placeholder="Contoh: 15" class="input sm:w-48">
                    <p class="text-xs text-slate-400 mt-1">Nomor lengkap: <b>{{ $nomorPratinjau }}</b> — bagian tengah diisi manual sesuai buku agenda surat.</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Isi Surat <span class="text-slate-400 font-normal">(otomatis dari template, boleh diedit)</span></label>
                    <textarea name="isi_surat" rows="8" class="input font-mono text-sm">{{ old('isi_surat', $isiSuratPreview) }}</textarea>
                </div>
            </div>
            @endif
        </div>

        <button type="submit" class="btn-primary h-[38px]">Simpan Pemanggilan</button>
    </form>
    @endif
</div>
@endsection
