<?php

return [
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],
    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Gateway WhatsApp untuk notifikasi Alfa ke orang tua. Contoh di sini
    // pakai Fonnte (https://fonnte.com) karena API-nya sederhana & populer
    // di Indonesia. Kalau pakai provider lain (Wablas, Woowa, dsb), cukup
    // sesuaikan URL & format request di app/Jobs/KirimNotifikasiAlfaWhatsapp.php.
    // Dua PERANGKAT (device) Fonnte yang sengaja dipisah, karena pengirim
    // dan penerimanya memang berbeda:
    //
    //   token       -> perangkat 1, nomor sekolah.
    //                  Mengirim pemberitahuan siswa Alfa ke ORANG TUA.
    //                  Dipakai app/Jobs/KirimNotifikasiAlfaWhatsapp.php.
    //
    //   token_guru  -> perangkat 2, nomor kepala sekolah.
    //                  Mengirim pengingat jurnal & absensi ke GURU.
    //                  Dipakai app/Jobs/KirimPengingatJurnalWhatsapp.php.
    //                  Biasanya TIDAK diisi di sini melainkan lewat menu
    //                  Pengaturan (tersimpan terenkripsi di database);
    //                  nilai di .env ini hanya cadangan bila sekolah lebih
    //                  suka menaruh rahasianya di berkas .env.
    //
    // URL-nya sama untuk keduanya — yang membedakan perangkat di Fonnte
    // adalah tokennya, bukan alamatnya.
    'fonnte' => [
        'token' => env('FONNTE_TOKEN'),
        'token_guru' => env('FONNTE_TOKEN_GURU'),
        'url' => env('FONNTE_URL', 'https://api.fonnte.com/send'),
    ],
];
