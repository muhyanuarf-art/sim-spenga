@extends('layouts.app')
@section('title', 'Pengaturan Sekolah')

@section('content')
<div class="space-y-6 max-w-2xl">
    <div class="card p-5">
        <p class="font-extrabold text-slate-800 text-lg mb-1">Pengaturan Sekolah</p>
        <p class="text-sm text-slate-400 mb-5">
            Data di bawah ini bersifat relatif tetap dan otomatis dipakai di semua halaman yang punya tombol
            <b>Cetak</b> (rekap absensi, jurnal kelas, laporan guru, rekapitulasi, dll) — menyesuaikan jabatan
            akun yang bersangkutan. Contoh hasilnya di baris tanda tangan:
            <span class="italic">"Bumiayu, 16 Agustus 2026 &middot; Wali Kelas 7A &middot; Nama, S.Pd. &middot; NIP. ..."</span>
        </p>

        <form method="POST" action="{{ route('pengaturan-sekolah.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Nama Sekolah</label>
                <input type="text" name="nama_sekolah" value="{{ old('nama_sekolah', $pengaturan->nama_sekolah) }}"
                       placeholder="Contoh: SMP Negeri 1 Bumiayu" class="input">
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Kabupaten/Kota</label>
                    <input type="text" name="kabupaten_kota" required
                           value="{{ old('kabupaten_kota', $pengaturan->kabupaten_kota) }}" class="input">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Provinsi</label>
                    <input type="text" name="provinsi" required
                           value="{{ old('provinsi', $pengaturan->provinsi) }}" class="input">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">
                    Format Lokasi Tanda Tangan <span class="text-slate-400 font-normal">(opsional)</span>
                </label>
                <input type="text" name="format_lokasi_ttd"
                       value="{{ old('format_lokasi_ttd', $pengaturan->format_lokasi_ttd) }}"
                       placeholder="Kosongkan untuk memakai Kabupaten/Kota di atas (\"{{ $pengaturan->kabupaten_kota }}\")"
                       class="input">
                <p class="text-xs text-slate-400 mt-1">
                    Isi hanya kalau teks lokasi di baris tanda tangan perlu berbeda dari Kabupaten/Kota di atas
                    (mis. "Kota Bumiayu"). Operator tetap bisa mengubahnya lagi secara manual saat mau Cetak.
                </p>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Nama Kepala Sekolah</label>
                    <input type="text" name="nama_kepala_sekolah"
                           value="{{ old('nama_kepala_sekolah', $pengaturan->nama_kepala_sekolah) }}" class="input">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">NIP Kepala Sekolah</label>
                    <input type="text" name="nip_kepala_sekolah"
                           value="{{ old('nip_kepala_sekolah', $pengaturan->nip_kepala_sekolah) }}" class="input">
                </div>
            </div>
            <p class="text-xs text-slate-400 -mt-2">
                Dipakai khusus di laporan yang ditandatangani Kepala Sekolah (mis. Rekapitulasi). Untuk laporan
                Wali Kelas / Guru Mapel, nama & NIP penandatangan otomatis diambil dari data akun yang bersangkutan.
            </p>

            <div class="pt-2">
                <button type="submit" class="btn-primary">💾 Simpan Pengaturan</button>
            </div>
        </form>
    </div>
</div>
@endsection
