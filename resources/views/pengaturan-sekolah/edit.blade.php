@extends('layouts.app')
@section('title', 'Pengaturan Sekolah')

@section('content')
{{-- Lebarnya disamakan dengan seluruh halaman lain (space-y-6 tanpa
     pembatas). Sebelumnya halaman ini satu-satunya yang dikunci
     "max-w-2xl", sehingga terlihat sempit dan tidak sebaris dengan
     halaman Pengaturan lainnya. Karena kini melebar, isiannya ditata
     ulang menjadi beberapa panel bergrid supaya tidak ada kotak isian
     yang memanjang sendirian selebar layar. --}}
<div class="space-y-6">
    <form method="POST" action="{{ route('pengaturan-sekolah.update') }}" class="space-y-6" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- ================= Identitas Aplikasi ================= --}}
        <x-panel judul="Identitas Aplikasi"
                 deskripsi="Logo yang tampil di dalam aplikasi sehari-hari — bukan untuk KOP surat."
                 ikon="fa-image">
            <div class="grid md:grid-cols-3 gap-5 items-start">
                <div>
                    <label class="label">Logo / Ikon SIM <span class="text-slate-400 font-normal">(opsional)</span></label>

                    <div class="flex items-center gap-3 mb-3">
                        {{-- Pratinjau persis seukuran & sebentuk kotak logo di sidebar,
                             supaya operator langsung tahu hasilnya nanti seperti apa. --}}
                        <div class="w-14 h-14 rounded-xl bg-brand-800 flex items-center justify-center overflow-hidden shrink-0 shadow-inner">
                            @if($pengaturan->logoAplikasiUrl())
                                <img src="{{ $pengaturan->logoAplikasiUrl() }}" alt="Logo aplikasi" class="w-full h-full object-contain p-1">
                            @else
                                <span class="font-extrabold text-white text-lg">{{ $pengaturan->inisialAplikasi() }}</span>
                            @endif
                        </div>
                        <div class="text-xs text-slate-400 leading-relaxed">
                            @if($pengaturan->logoAplikasiUrl())
                                Logo terpasang.
                                <label class="flex items-center gap-1.5 text-red-500 mt-1 cursor-pointer">
                                    <input type="checkbox" name="hapus_logo_aplikasi" value="1"> Hapus logo ini
                                </label>
                            @else
                                Belum ada logo — sementara dipakai inisial
                                <b class="text-slate-600">{{ $pengaturan->inisialAplikasi() }}</b>
                                dari Nama Sekolah.
                            @endif
                        </div>
                    </div>

                    <input type="file" name="logo_aplikasi" accept="image/*" class="input">
                    @error('logo_aplikasi')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Logo ini muncul di <b>tiga tempat</b>: pojok kiri atas sidebar, halaman login guru &amp;
                        orang tua, serta sebagai ikon di tab browser (favicon).
                    </p>
                    <ul class="text-xs text-slate-400 mt-2 space-y-1 list-disc list-inside">
                        <li>Sebaiknya berbentuk <b>persegi</b> (mis. 512&times;512 piksel) agar tidak terpotong.</li>
                        <li>Format PNG dengan latar transparan memberi hasil paling rapi. Maksimal 2 MB.</li>
                        <li>Kalau dikosongkan, aplikasi memakai inisial dari Nama Sekolah.</li>
                    </ul>
                </div>
            </div>
        </x-panel>

        {{-- ================= KOP Surat ================= --}}
        <x-panel judul="KOP Surat"
                 deskripsi="Muncul di bagian paling atas hasil Cetak/Export PDF seluruh laporan — tidak tampil di layar biasa. Baris yang dikosongkan otomatis tidak ditampilkan."
                 ikon="fa-file-lines">
            <div class="grid md:grid-cols-2 gap-5 mb-5">
                @foreach(['kiri' => 'Logo Kiri', 'kanan' => 'Logo Kanan'] as $sisi => $labelLogo)
                    @php $url = $sisi === 'kiri' ? $pengaturan->logoKiriUrl() : $pengaturan->logoKananUrl(); @endphp
                    <div>
                        <label class="label">{{ $labelLogo }} <span class="text-slate-400 font-normal">(opsional)</span></label>
                        @if($url)
                            <div class="flex items-center gap-3 mb-2">
                                <img src="{{ $url }}" alt="{{ $labelLogo }}" class="w-14 h-14 object-contain border border-slate-200 rounded-lg p-1 bg-white">
                                <label class="flex items-center gap-1.5 text-xs text-red-500 cursor-pointer">
                                    <input type="checkbox" name="hapus_logo_{{ $sisi }}" value="1"> Hapus logo ini
                                </label>
                            </div>
                        @endif
                        <input type="file" name="logo_{{ $sisi }}" accept="image/*" class="input">
                        @error('logo_'.$sisi)<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>

            <div class="grid md:grid-cols-2 gap-x-5 gap-y-4">
                <div class="md:col-span-2">
                    <label class="label">Nama Sekolah</label>
                    <input type="text" name="nama_sekolah" value="{{ old('nama_sekolah', $pengaturan->nama_sekolah) }}"
                           placeholder="Contoh: SMP NEGERI 3 BUMIAYU" class="input">
                </div>
                <div>
                    <label class="label">Pemerintah Daerah <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input type="text" name="pemerintah_daerah" value="{{ old('pemerintah_daerah', $pengaturan->pemerintah_daerah) }}"
                           placeholder="Contoh: PEMERINTAH KABUPATEN BREBES" class="input">
                </div>
                <div>
                    <label class="label">Instansi Induk <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input type="text" name="instansi_induk" value="{{ old('instansi_induk', $pengaturan->instansi_induk) }}"
                           placeholder="Contoh: DINAS PENDIDIKAN PEMUDA DAN OLAHRAGA" class="input">
                </div>
                <div>
                    <label class="label">Unit Kerja <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input type="text" name="unit_kerja" value="{{ old('unit_kerja', $pengaturan->unit_kerja) }}"
                           placeholder="Contoh: UPT SATUAN PENDIDIKAN FORMAL" class="input">
                </div>
                <div>
                    <label class="label">Kecamatan <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input type="text" name="kecamatan" value="{{ old('kecamatan', $pengaturan->kecamatan) }}"
                           placeholder="Contoh: KECAMATAN BUMIAYU" class="input">
                </div>
                <div class="md:col-span-2">
                    <label class="label">Alamat Sekolah <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input type="text" name="alamat_sekolah" value="{{ old('alamat_sekolah', $pengaturan->alamat_sekolah) }}"
                           placeholder="Contoh: Jalan Desa Langkap Bumiayu Brebes 52273" class="input">
                </div>
                <div>
                    <label class="label">Website Sekolah <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input type="text" name="website_sekolah" value="{{ old('website_sekolah', $pengaturan->website_sekolah) }}"
                           placeholder="Contoh: www.smpn3bumiayu.sch.id" class="input">
                </div>
                <div>
                    <label class="label">Email Sekolah <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input type="text" name="email_sekolah" value="{{ old('email_sekolah', $pengaturan->email_sekolah) }}"
                           placeholder="Boleh lebih dari 1, pisahkan dengan &quot; / &quot;" class="input">
                </div>
            </div>
        </x-panel>

        {{-- ================= Tanda Tangan ================= --}}
        <x-panel judul="Lokasi & Penanda Tangan"
                 deskripsi="Dipakai otomatis di baris tanda tangan seluruh halaman yang punya tombol Cetak."
                 ikon="fa-pen-nib">
            <div class="grid md:grid-cols-2 gap-x-5 gap-y-4">
                <div>
                    <label class="label">Kabupaten/Kota</label>
                    <input type="text" name="kabupaten_kota" required
                           value="{{ old('kabupaten_kota', $pengaturan->kabupaten_kota) }}" class="input">
                </div>
                <div>
                    <label class="label">Provinsi</label>
                    <input type="text" name="provinsi" required
                           value="{{ old('provinsi', $pengaturan->provinsi) }}" class="input">
                </div>

                <div class="md:col-span-2">
                    <label class="label">Format Lokasi Tanda Tangan <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input type="text" name="format_lokasi_ttd"
                           value="{{ old('format_lokasi_ttd', $pengaturan->format_lokasi_ttd) }}"
                           placeholder="Kosongkan untuk memakai Kabupaten/Kota di atas (&quot;{{ $pengaturan->kabupaten_kota }}&quot;)"
                           class="input">
                    <p class="text-xs text-slate-400 mt-1">
                        Isi hanya kalau teks lokasi di baris tanda tangan perlu berbeda dari Kabupaten/Kota
                        (mis. &ldquo;Kota Bumiayu&rdquo;). Operator tetap bisa mengubahnya lagi secara manual saat mau Cetak.
                    </p>
                </div>

                <div>
                    <label class="label">Nama Kepala Sekolah</label>
                    <input type="text" name="nama_kepala_sekolah"
                           value="{{ old('nama_kepala_sekolah', $pengaturan->nama_kepala_sekolah) }}" class="input">
                </div>
                <div>
                    <label class="label">NIP Kepala Sekolah</label>
                    <input type="text" name="nip_kepala_sekolah"
                           value="{{ old('nip_kepala_sekolah', $pengaturan->nip_kepala_sekolah) }}" class="input">
                </div>

                <p class="md:col-span-2 text-xs text-slate-400">
                    Nama &amp; NIP Kepala Sekolah dipakai khusus di laporan yang ditandatangani beliau (mis.
                    Rekapitulasi). Untuk laporan Wali Kelas / Guru Mapel, penanda tangannya otomatis diambil
                    dari data akun yang bersangkutan.
                </p>
            </div>
        </x-panel>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary">
                <i class="fa-solid fa-floppy-disk mr-1.5"></i> Simpan Pengaturan
            </button>
        </div>
    </form>

    {{-- Status lisensi — di luar form karena tidak ada yang bisa diubah
         dari sini. Lihat App\Support\Lisensi. --}}
    @php($lisensi = \App\Support\Lisensi::catatan())
    <x-panel judul="Lisensi Aplikasi"
             deskripsi="Aplikasi ini hanya berjalan pada pemasangan yang sudah diaktifkan dengan nomor seri resmi.">
        <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
            <div>
                <dt class="text-xs font-semibold text-slate-500">Dilisensikan untuk</dt>
                <dd class="text-slate-700">{{ config('lisensi.pemegang') }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold text-slate-500">Status</dt>
                <dd>
                    <span class="badge bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                        <i class="fa-solid fa-circle-check mr-1.5"></i> Aktif
                    </span>
                </dd>
            </div>
            <div>
                <dt class="text-xs font-semibold text-slate-500">Diaktifkan pada</dt>
                <dd class="text-slate-700">
                    {{ $lisensi?->diaktifkan_at?->translatedFormat('d F Y, H:i') ?? '-' }}
                </dd>
            </div>
            <div>
                <dt class="text-xs font-semibold text-slate-500">Terikat alamat server</dt>
                <dd class="text-slate-700 font-mono text-xs">
                    {{ config('lisensi.terikat_host') ? ($lisensi?->host ?? '-') : 'tidak diikat' }}
                </dd>
            </div>
        </dl>

        <p class="mt-4 text-xs text-slate-500 leading-relaxed">
            <i class="fa-solid fa-circle-info mr-1"></i>
            Nomor serinya sendiri tidak disimpan di mana pun — yang tercatat hanya sidiknya, sehingga
            tidak dapat dibaca balik dari aplikasi maupun database. Simpan nomor seri Anda di tempat aman:
            @if(config('lisensi.terikat_host'))
                memindahkan aplikasi ke server atau domain lain menuntut nomor seri itu lagi.
            @else
                nomor seri diperlukan lagi setiap kali aplikasi dipasang ulang.
            @endif
        </p>
    </x-panel>
</div>
@endsection
