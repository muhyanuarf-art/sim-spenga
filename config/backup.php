<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kata Sandi Backup — WAJIB DIISI
    |--------------------------------------------------------------------------
    |
    | Backup berisi dump database LENGKAP beserta APP_KEY. Siapa pun yang
    | memegang keduanya bisa membaca seluruh data siswa, mendekripsi token
    | WhatsApp, dan menyusun sendiri baris lisensi yang sah. Berkas backup
    | karena itu setara kunci induk sekolah, bukan sekadar arsip.
    |
    | Maka backup TIDAK PERNAH dibuat tanpa enkripsi. Perintah backup:buat
    | akan menolak berjalan bila nilai ini kosong.
    |
    | YANG PALING PENTING: kata sandi ini harus disimpan DI LUAR server —
    | di ponsel Kepala Sekolah, di brankas, di pengelola kata sandi. Kalau
    | ia ikut tersimpan di komputer yang sama dan komputer itu diambil
    | orang, enkripsinya tidak menolong apa pun.
    |
    | Sebaliknya, untuk ancaman yang paling mungkin terjadi — flashdisk
    | hilang, akun Google Drive kebobolan — berkas terenkripsi tanpa kata
    | sandinya tidak berarti apa-apa.
    |
    */
    'sandi' => env('BACKUP_SANDI'),

    /*
    |--------------------------------------------------------------------------
    | Folder Tujuan
    |--------------------------------------------------------------------------
    |
    | Bawaannya SENGAJA di luar folder proyek, supaya backup selamat kalau
    | folder aplikasi dihapus, dipindah, atau di-install ulang teknisi.
    | Jangan pernah menaruhnya di dalam public/ — apa pun di sana bisa
    | diunduh siapa saja yang menebak alamatnya.
    |
    | Cara terbaik untuk sekolah: pasang Google Drive Desktop atau OneDrive
    | di komputer server, lalu arahkan nilai ini ke folder sinkronnya.
    | Bagi perintah ini itu hanya folder biasa, tetapi salinannya otomatis
    | keluar dari komputer tanpa siapa pun perlu ingat mencolok flashdisk.
    |
    */
    'tujuan' => env('BACKUP_TUJUAN', 'C:\\backup-sim-spenga'),

    /*
    |--------------------------------------------------------------------------
    | Berapa Backup Terakhir yang Disimpan
    |--------------------------------------------------------------------------
    |
    | Satu backup sekitar 5 MB pada ukuran data sekarang, jadi 30 salinan
    | pun hanya sekitar 150 MB. Menyimpan banyak itu penting: kerusakan
    | data sering baru ketahuan berhari-hari kemudian, dan backup kemarin
    | yang sudah ikut rusak tidak menolong siapa pun.
    |
    */
    'simpan' => (int) env('BACKUP_SIMPAN', 30),

    /*
    |--------------------------------------------------------------------------
    | Letak mysqldump
    |--------------------------------------------------------------------------
    |
    | Dikosongkan berarti dicari otomatis di dalam folder Laragon.
    |
    */
    'mysqldump' => env('BACKUP_MYSQLDUMP'),

];
