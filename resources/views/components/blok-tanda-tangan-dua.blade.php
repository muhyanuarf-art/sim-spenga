{{--
    Blok tanda tangan 2 penanda tangan, dipakai di halaman-halaman Cetak yang
    perlu diketahui Kepala Sekolah SEKALIGUS ditandatangani penanggung jawab
    kelas (mis. Rekap Absensi Bulanan & Jurnal Mengajar Kelas). Kepala Sekolah
    selalu di pojok kiri (baris "Mengetahui"), penanda tangan kedua (mis. Wali
    Kelas) di pojok kanan — sesuai format cetak resmi sekolah.

    Kota & tanggal sudah otomatis terisi dari Pengaturan Sekolah / tanggal
    hari ini, tapi operator bisa mengetik ulang isinya langsung sebelum
    menekan Cetak — nilai yang sedang tampil di layar itulah yang ikut
    tercetak, jadi tidak perlu menu/tombol simpan terpisah.

    Props:
    - jabatanKanan (string, wajib)   : mis. "Wali Kelas 7A"
    - namaKanan    (string, opsional): nama penanda tangan kanan
    - nipKanan     (string, opsional): NIP penanda tangan kanan
    - jabatanKiri  (string, opsional): default "Kepala Sekolah"
    - namaKiri     (string, opsional): default nama kepala sekolah dari Pengaturan Sekolah
    - nipKiri      (string, opsional): default NIP kepala sekolah dari Pengaturan Sekolah
--}}
@props([
    'jabatanKanan',
    'namaKanan' => null,
    'nipKanan' => null,
    'jabatanKiri' => 'Kepala Sekolah',
    'namaKiri' => null,
    'nipKiri' => null,
])

@php
    $kotaDefault = $pengaturanSekolahGlobal->lokasiTtd();
    $tanggalDefault = now()->translatedFormat('d F Y');
    $namaKiri = $namaKiri ?: $pengaturanSekolahGlobal->nama_kepala_sekolah;
    $nipKiri = $nipKiri ?: $pengaturanSekolahGlobal->nip_kepala_sekolah;
@endphp

<div class="cetak-utuh mt-8 print:mt-4">
    <p class="text-sm text-slate-700 text-right mb-4">
        <input type="text" value="{{ $kotaDefault }}"
               class="text-right bg-transparent border-b border-dashed border-slate-300 focus:outline-none focus:border-brand-500 w-28 print:border-b-0"
               aria-label="Kota tanda tangan (bisa diubah sebelum cetak)">,
        <input type="text" value="{{ $tanggalDefault }}"
               class="text-left bg-transparent border-b border-dashed border-slate-300 focus:outline-none focus:border-brand-500 w-36 print:border-b-0"
               aria-label="Tanggal tanda tangan (bisa diubah sebelum cetak)">
    </p>
    <p class="text-xs text-slate-400 text-right mb-1 print:hidden"><i class="fa-solid fa-pen mr-1.5"></i> Kota &amp; tanggal bisa diketik ulang di atas sebelum Cetak</p>

    <div class="flex justify-between gap-6">
        <div class="text-sm text-slate-700 text-center w-72">
            <p class="mb-1">Mengetahui</p>
            <p>{{ $jabatanKiri }},</p>
            <div class="h-16"></div>
            <p class="font-bold underline underline-offset-2">{{ $namaKiri ?: '............................' }}</p>
            <p>NIP. {{ $nipKiri ?: '............................' }}</p>
        </div>

        <div class="text-sm text-slate-700 text-center w-72">
            <p class="mb-1">&nbsp;</p>
            <p>{{ $jabatanKanan }},</p>
            <div class="h-16"></div>
            <p class="font-bold underline underline-offset-2">{{ $namaKanan ?: '............................' }}</p>
            <p>NIP. {{ $nipKanan ?: '............................' }}</p>
        </div>
    </div>
</div>
