<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Letak Chrome / Chromium
    |--------------------------------------------------------------------------
    |
    | Dipakai mencetak laporan menjadi PDF dengan tampilan yang SAMA PERSIS
    | dengan tombol Cetak di layar — termasuk KOP surat, yang hanya muncul
    | lewat aturan @media print dan tidak dipahami pustaka PDF biasa.
    |
    | Dikosongkan berarti dicari otomatis di tempat-tempat yang lazim
    | (lihat App\Support\PencetakChrome). Isi ini hanya bila Chromium di
    | server Anda berada di tempat yang tidak lazim.
    |
    | Bila tidak ditemukan sama sekali, arsip tetap dibuat memakai mPDF —
    | jadi, tetapi tampilannya sederhana. Pesan itu ikut tercantum di
    | RINGKASAN.pdf supaya tidak ada yang mengira arsipnya rusak.
    |
    */
    'chrome' => env('ARSIP_CHROME'),

];
