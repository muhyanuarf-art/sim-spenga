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
    <div class="card p-5 space-y-4">
        <p class="font-bold text-slate-800 text-sm">3. Lengkapi &amp; Simpan</p>

        {{-- Tanggal — auto-reload begitu diklik, supaya Nomor Surat di
             bawah (pratinjau saja, bagian nomor urutnya belum terisi)
             otomatis ikut update bulan-romawi/tahunnya. --}}
        <form method="GET" class="grid sm:grid-cols-2 gap-4">
            <input type="hidden" name="jenis_surat_id" value="{{ $jenisSurat->id }}">
            <input type="hidden" name="siswa_id" value="{{ $siswaTerpilih->id }}">
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Tanggal Surat</label>
                <input type="date" name="tanggal" value="{{ $tanggal }}" required class="input" onchange="this.form.submit()">
            </div>
            @if($jenisSurat->tipe_formulir === \App\Models\JenisSurat::TIPE_BEBAS)
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Tanggal Acara/Pemanggilan</label>
                <input type="date" name="tanggal_acara" value="{{ $tanggalAcara }}" class="input" onchange="this.form.submit()">
            </div>
            @endif
        </form>

        <div class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm">
            <span class="text-slate-500">Nomor Surat</span> :
            <span class="font-semibold text-slate-700">{{ $nomorPratinjau }}</span>
            <span class="text-slate-400"> — bagian tengah diisi manual di bawah (wajib)</span>
        </div>

        <form method="POST" action="{{ route('surat.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="jenis_surat_id" value="{{ $jenisSurat->id }}">
            <input type="hidden" name="siswa_id" value="{{ $siswaTerpilih->id }}">
            <input type="hidden" name="tanggal" value="{{ $tanggal }}">
            @if($jenisSurat->tipe_formulir === \App\Models\JenisSurat::TIPE_BEBAS)
                <input type="hidden" name="tanggal_acara" value="{{ $tanggalAcara }}">
            @endif

            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Nomor Urut Surat <span class="text-red-500">*</span></label>
                <input type="text" name="nomor_urut" value="{{ old('nomor_urut') }}" required placeholder="Contoh: 15" class="input sm:w-48">
                <p class="text-xs text-slate-400 mt-1">Diisi manual sesuai buku agenda surat — bagian "422/<b>...</b>/BK/{{ \Carbon\Carbon::parse($tanggal)->translatedFormat('m') }}/..." di atas.</p>
            </div>

            @if($jenisSurat->tipe_formulir === \App\Models\JenisSurat::TIPE_BEBAS)
                {{-- Surat Panggilan Orang Tua/Wali — template bebas seperti sebelumnya. --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Waktu Acara</label>
                    <input type="time" name="waktu_acara" value="{{ old('waktu_acara', $waktuAcara) }}" class="input sm:w-48">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Isi Surat <span class="text-slate-400 font-normal">(otomatis digabung dari template, boleh diedit)</span></label>
                    <textarea name="isi" rows="10" required class="input font-mono text-sm">{{ old('isi', $isiGabungan) }}</textarea>
                </div>

            @elseif($jenisSurat->tipe_formulir === \App\Models\JenisSurat::TIPE_IZIN_MENINGGALKAN_PELAJARAN)
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Alamat</label>
                        <input type="text" name="alamat" value="{{ old('alamat') }}" class="input">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Diberi Ijin Meninggalkan Pelajaran Mulai Jam Ke</label>
                        <input type="text" name="jam_ke" value="{{ old('jam_ke') }}" placeholder="Contoh: 5" class="input">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Keperluan <span class="text-red-500">*</span></label>
                    <textarea name="keperluan" rows="2" required class="input">{{ old('keperluan') }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Keterangan Lain <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <textarea name="keterangan_lain" rows="2" class="input">{{ old('keterangan_lain') }}</textarea>
                </div>

            @elseif($jenisSurat->tipe_formulir === \App\Models\JenisSurat::TIPE_KETERANGAN_TERLAMBAT)
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Alamat</label>
                        <input type="text" name="alamat" value="{{ old('alamat') }}" class="input">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Terlambat <span class="text-red-500">*</span></label>
                        <input type="text" name="terlambat" value="{{ old('terlambat') }}" placeholder="Contoh: 30 menit / jam ke-1" required class="input">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Alasan Terlambat <span class="text-red-500">*</span></label>
                    <textarea name="alasan_terlambat" rows="2" required class="input">{{ old('alasan_terlambat') }}</textarea>
                </div>

            @elseif($jenisSurat->tipe_formulir === \App\Models\JenisSurat::TIPE_PERNYATAAN_PELANGGARAN)
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Pelanggaran Ke <span class="text-red-500">*</span></label>
                    <input type="number" name="pelanggaran_ke" value="{{ old('pelanggaran_ke', $pelanggaranKeBerikutnya) }}" min="1" required class="input sm:w-32">
                    <p class="text-xs text-slate-400 mt-1">Otomatis dihitung dari riwayat surat pelanggaran siswa ini sebelumnya — boleh diubah kalau perlu.</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Pelanggaran Disiplin Sekolah Berupa <span class="text-red-500">*</span></label>
                    <textarea name="pelanggaran" rows="3" required class="input">{{ old('pelanggaran') }}</textarea>
                </div>
            @endif

            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Keterangan <span class="text-slate-400 font-normal">(opsional, internal — tidak ikut tercetak)</span></label>
                <input type="text" name="keterangan" value="{{ old('keterangan') }}" class="input">
            </div>

            <button type="submit" class="btn-primary h-[38px]">Simpan Surat</button>
        </form>
    </div>
    @endif
</div>
@endsection
