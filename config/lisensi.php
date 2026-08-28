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

];
