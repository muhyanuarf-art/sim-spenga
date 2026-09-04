<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sidik Kunci Lisensi
    |--------------------------------------------------------------------------
    |
    | Yang disimpan di sini BUKAN nomor serinya, melainkan sidik SHA-256-nya.
    | Nomor serinya sendiri tidak pernah ada di dalam kode, jadi tidak bisa
    | dibaca balik oleh siapa pun yang membuka berkas ini — termasuk kalau
    | kodenya bocor. Yang bisa dilakukan berkas ini hanyalah MEMERIKSA
    | apakah nomor seri yang diketik cocok.
    |
    | Kalau suatu saat perlu menerbitkan nomor seri baru, hitung sidiknya
    | dengan:  hash('sha256', 'sim-spenga|' . NOMOR_SERI_TANPA_TANDA_HUBUNG)
    | lalu ganti nilai di bawah (atau isi LISENSI_HASH di .env).
    |
    */

    'hash' => env('LISENSI_HASH', 'a432ae77eea339adf4e4ac594cd6e4ac9bd4807daad6e672159e07fc5a2f4cb3'),

    /*
    |--------------------------------------------------------------------------
    | Terikat Alamat Server
    |--------------------------------------------------------------------------
    |
    | Kalau true, aktivasi dicatat bersama alamat (host) tempat aplikasi
    | dijalankan. Menyalin seluruh aplikasi BESERTA databasenya ke server
    | lain tidak otomatis ikut aktif — pemasang harus memasukkan nomor seri
    | lagi di sana. Ini yang membuat nomor seri benar-benar berarti, bukan
    | sekadar sekali klik lalu bisa digandakan.
    |
    | Berpindah domain pada server yang sama juga meminta aktivasi ulang.
    | Matikan (false) bila alamatnya memang sering berubah.
    |
    */

    'terikat_host' => env('LISENSI_TERIKAT_HOST', true),

    /*
    |--------------------------------------------------------------------------
    | Nama Pemegang Lisensi
    |--------------------------------------------------------------------------
    |
    | Ditampilkan di halaman aktivasi & Pengaturan Sekolah supaya jelas
    | aplikasi ini dilisensikan untuk siapa.
    |
    */

    'pemegang' => env('LISENSI_PEMEGANG', 'SMP Negeri 3 Bumiayu'),

    /*
    |--------------------------------------------------------------------------
    | MODE LISENSI
    |--------------------------------------------------------------------------
    |
    |   'lokal'  — cara lama: nomor seri diperiksa di dalam aplikasi ini
    |              sendiri (App\Support\Lisensi). Dipertahankan supaya
    |              pemasangan yang sudah berjalan tidak mendadak terkunci.
    |
    |   'server' — nomor seri ditukar ke ffproduction.com dengan SURAT
    |              AKTIVASI bertanda tangan. Masa berlaku, perpanjangan,
    |              dan pencabutan sepenuhnya diatur dari sana.
    |
    | =====================================================================
    | SEBELUM DIEDARKAN: HAPUS SAKLAR INI
    | =====================================================================
    | Selama nilainya masih dibaca dari .env, siapa pun yang bisa menyunting
    | .env di server bisa mengembalikannya ke 'lokal' dan melewati
    | pemeriksaan. Saklar ini HANYA alat bantu masa peralihan.
    |
    | Begitu ffproduction.com hidup dan aktivasinya teruji, ganti baris di
    | bawah menjadi nilai tetap:
    |
    |     'mode' => 'server',
    |
    | dan buang seluruh cabang 'lokal' dari App\Support\Lisensi. Pada
    | pemasangan yang dikelola FF Production sendiri hal ini sebetulnya
    | tidak kritis — sekolah tidak pernah memegang berkasnya — tetapi
    | meninggalkan pintu yang tidak dipakai bukan kebiasaan yang baik.
    |
    */
    'mode' => env('LISENSI_MODE', 'lokal'),

    /*
    |--------------------------------------------------------------------------
    | Server Lisensi
    |--------------------------------------------------------------------------
    */
    'server' => env('LISENSI_SERVER', 'https://ffproduction.com'),

    /*
    |--------------------------------------------------------------------------
    | Kredensial Pemasangan
    |--------------------------------------------------------------------------
    |
    | Diisi FF PRODUCTION sekali, saat memasang aplikasi ini di hosting —
    | bukan oleh sekolah, dan tidak pernah diketik siapa pun di sekolah.
    | Nilainya diperoleh dari `php artisan sekolah:daftar` di server
    | ffproduction.com.
    |
    | Nomor seri sengaja ditinggalkan: ia hanya masuk akal bila penerbitnya
    | TIDAK ikut memasang. Pada model terkelola, ia cuma menambah telepon
    | dan salah ketik — sekaligus membuat lisensi terlihat oleh sekolah,
    | padahal seharusnya tidak pernah terasa ada.
    |
    */
    'kode' => env('LISENSI_KODE', ''),
    'token' => env('LISENSI_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Kunci Publik Penandatangan
    |--------------------------------------------------------------------------
    |
    | Dibuat dengan `php artisan lisensi:buat-kunci` di komputer FF
    | Production. Yang ditaruh di sini HANYA bagian publiknya — aman
    | dilihat siapa saja, karena ia cuma bisa MEMERIKSA tanda tangan,
    | tidak bisa membuatnya. Pasangan rahasianya tidak boleh pernah
    | meninggalkan server ffproduction.com.
    |
    */
    'kunci_publik' => env('LISENSI_KUNCI_PUBLIK', ''),

    /*
    |--------------------------------------------------------------------------
    | Umur Surat & Jarak Menyapa
    |--------------------------------------------------------------------------
    |
    | Surat aktivasi berlaku singkat lalu diperbarui otomatis. Umurnya
    | ditentukan SERVER (nilai di sini hanya cadangan bila server tidak
    | menyebutkannya).
    |
    | Kenapa tidak nol — yaitu memeriksa ke server pada setiap permintaan?
    | Karena itu menggantungkan seluruh sekolah pelanggan pada kesempurnaan
    | ffproduction.com. Server tersendat dua jam berarti semua sekolah
    | berhenti bekerja serentak. Bantalan 24 jam membuat gangguan singkat
    | tidak terasa siapa pun, sementara langganan yang habis tetap menutup
    | aplikasi dalam sehari.
    |
    */
    'umur_surat_jam' => (int) env('LISENSI_UMUR_SURAT_JAM', 24),
    'jarak_sapa_jam' => (int) env('LISENSI_JARAK_SAPA_JAM', 6),

];
