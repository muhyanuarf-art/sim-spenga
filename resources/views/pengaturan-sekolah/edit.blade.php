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

        <form method="POST" action="{{ route('pengaturan-sekolah.update') }}" class="space-y-6" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="border-b border-slate-100 pb-6">
                <p class="font-bold text-slate-700 text-sm mb-1">KOP Surat</p>
                <p class="text-xs text-slate-400 mb-4">
                    Muncul di bagian paling atas hasil Cetak/Export PDF di semua laporan (bukan tampil di layar
                    biasa) — persis seperti kop surat resmi. Baris yang dikosongkan otomatis tidak ditampilkan.
                </p>

                <div class="grid sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Logo Kiri <span class="text-slate-400 font-normal">(opsional)</span></label>
                        @if($pengaturan->logoKiriUrl())
                            <div class="flex items-center gap-3 mb-2">
                                <img src="{{ $pengaturan->logoKiriUrl() }}" class="w-14 h-14 object-contain border border-slate-200 rounded-lg p-1">
                                <label class="flex items-center gap-1.5 text-xs text-red-500">
                                    <input type="checkbox" name="hapus_logo_kiri" value="1"> Hapus logo ini
                                </label>
                            </div>
                        @endif
                        <input type="file" name="logo_kiri" accept="image/*" class="input">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Logo Kanan <span class="text-slate-400 font-normal">(opsional)</span></label>
                        @if($pengaturan->logoKananUrl())
                            <div class="flex items-center gap-3 mb-2">
                                <img src="{{ $pengaturan->logoKananUrl() }}" class="w-14 h-14 object-contain border border-slate-200 rounded-lg p-1">
                                <label class="flex items-center gap-1.5 text-xs text-red-500">
                                    <input type="checkbox" name="hapus_logo_kanan" value="1"> Hapus logo ini
                                </label>
                            </div>
                        @endif
                        <input type="file" name="logo_kanan" accept="image/*" class="input">
                    </div>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Pemerintah Daerah <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input type="text" name="pemerintah_daerah" value="{{ old('pemerintah_daerah', $pengaturan->pemerintah_daerah) }}"
                               placeholder="Contoh: PEMERINTAH KABUPATEN BREBES" class="input">
                    </div>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Instansi Induk <span class="text-slate-400 font-normal">(opsional)</span></label>
                            <input type="text" name="instansi_induk" value="{{ old('instansi_induk', $pengaturan->instansi_induk) }}"
                                   placeholder="Contoh: DINAS PENDIDIKAN PEMUDA DAN OLAHRAGA" class="input">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Unit Kerja <span class="text-slate-400 font-normal">(opsional)</span></label>
                            <input type="text" name="unit_kerja" value="{{ old('unit_kerja', $pengaturan->unit_kerja) }}"
                                   placeholder="Contoh: UPT SATUAN PENDIDIKAN FORMAL" class="input">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Nama Sekolah</label>
                        <input type="text" name="nama_sekolah" value="{{ old('nama_sekolah', $pengaturan->nama_sekolah) }}"
                               placeholder="Contoh: SMP NEGERI 3 BUMIAYU" class="input">
                    </div>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Kecamatan <span class="text-slate-400 font-normal">(opsional)</span></label>
                            <input type="text" name="kecamatan" value="{{ old('kecamatan', $pengaturan->kecamatan) }}"
                                   placeholder="Contoh: KECAMATAN BUMIAYU" class="input">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Alamat Sekolah <span class="text-slate-400 font-normal">(opsional)</span></label>
                            <input type="text" name="alamat_sekolah" value="{{ old('alamat_sekolah', $pengaturan->alamat_sekolah) }}"
                                   placeholder="Contoh: Jalan Desa Langkap Bumiayu Brebes 52273" class="input">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Website Sekolah <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input type="text" name="website_sekolah" value="{{ old('website_sekolah', $pengaturan->website_sekolah) }}"
                               placeholder="Contoh: www.smpn3bumiayu.sch.id" class="input">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Email Sekolah <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input type="text" name="email_sekolah" value="{{ old('email_sekolah', $pengaturan->email_sekolah) }}"
                               placeholder="Boleh lebih dari 1, pisahkan dengan &quot; / &quot;" class="input">
                    </div>
                </div>
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
                       placeholder="Kosongkan untuk memakai Kabupaten/Kota di atas (&quot;{{ $pengaturan->kabupaten_kota }}&quot;)"
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
                <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk mr-1.5"></i> Simpan Pengaturan</button>
            </div>
        </form>
    </div>
</div>
@endsection
