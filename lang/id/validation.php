<?php

/*
|--------------------------------------------------------------------------
| PESAN VALIDASI BAWAAN LARAVEL — VERSI BAHASA INDONESIA
|--------------------------------------------------------------------------
|
| APP_LOCALE aplikasi ini 'id', tapi sebelumnya folder lang/ belum ada sama
| sekali sehingga Laravel jatuh ke pesan bawaannya yang berbahasa Inggris.
| Akibatnya operator melihat campuran dua bahasa dalam satu form: pesan
| yang ditulis sendiri di controller berbahasa Indonesia ("Pilih minimal
| satu kelas..."), sementara pesan aturan standar berbahasa Inggris
| ("The tanggal kejadian field must be a date before or equal to today.").
|
| Berkas ini menyeragamkannya. Isinya = seluruh kunci bawaan Laravel 11,
| diterjemahkan dengan gaya yang sama seperti pesan buatan sendiri di
| controller: menyapa operator sekolah, bukan programmer.
|
| CATATAN — :attribute diisi dari daftar 'attributes' di bagian bawah.
| Kalau sebuah field belum terdaftar di sana, Laravel memakai nama
| kolomnya dengan garis bawah diganti spasi ("tanggal kejadian"), yang
| masih terbaca. Jadi daftar itu boleh dilengkapi kapan saja.
*/

return [

    'accepted' => 'Kolom :attribute harus disetujui.',
    'accepted_if' => 'Kolom :attribute harus disetujui bila :other bernilai :value.',
    'active_url' => 'Kolom :attribute harus berupa URL yang valid.',
    'after' => 'Kolom :attribute harus berisi tanggal setelah :date.',
    'after_or_equal' => 'Kolom :attribute harus berisi tanggal :date atau sesudahnya.',
    'alpha' => 'Kolom :attribute hanya boleh berisi huruf.',
    'alpha_dash' => 'Kolom :attribute hanya boleh berisi huruf, angka, strip, dan garis bawah.',
    'alpha_num' => 'Kolom :attribute hanya boleh berisi huruf dan angka.',
    'array' => 'Kolom :attribute harus berupa daftar.',
    'ascii' => 'Kolom :attribute hanya boleh berisi huruf, angka, dan simbol biasa.',
    'before' => 'Kolom :attribute harus berisi tanggal sebelum :date.',
    'before_or_equal' => 'Kolom :attribute harus berisi tanggal :date atau sebelumnya.',
    'between' => [
        'array' => 'Kolom :attribute harus berisi antara :min sampai :max item.',
        'file' => 'Ukuran berkas :attribute harus antara :min sampai :max kilobyte.',
        'numeric' => 'Kolom :attribute harus bernilai antara :min sampai :max.',
        'string' => 'Kolom :attribute harus terdiri dari :min sampai :max karakter.',
    ],
    'boolean' => 'Kolom :attribute hanya boleh bernilai ya atau tidak.',
    'can' => 'Kolom :attribute berisi nilai yang tidak diizinkan.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'contains' => 'Kolom :attribute belum berisi nilai yang diperlukan.',
    'current_password' => 'Kata sandi yang Anda masukkan salah.',
    'date' => 'Kolom :attribute harus berisi tanggal yang valid.',
    'date_equals' => 'Kolom :attribute harus berisi tanggal :date.',
    'date_format' => 'Kolom :attribute harus sesuai format :format.',
    'decimal' => 'Kolom :attribute harus memiliki :decimal angka di belakang koma.',
    'declined' => 'Kolom :attribute harus ditolak.',
    'declined_if' => 'Kolom :attribute harus ditolak bila :other bernilai :value.',
    'different' => 'Kolom :attribute dan :other tidak boleh sama.',
    'digits' => 'Kolom :attribute harus terdiri dari :digits angka.',
    'digits_between' => 'Kolom :attribute harus terdiri dari :min sampai :max angka.',
    'dimensions' => 'Ukuran gambar pada :attribute tidak sesuai.',
    'distinct' => 'Kolom :attribute berisi nilai yang sama dua kali.',
    'doesnt_end_with' => 'Kolom :attribute tidak boleh diakhiri salah satu dari: :values.',
    'doesnt_start_with' => 'Kolom :attribute tidak boleh diawali salah satu dari: :values.',
    'email' => 'Kolom :attribute harus berupa alamat email yang valid.',
    'ends_with' => 'Kolom :attribute harus diakhiri salah satu dari: :values.',
    'enum' => ':attribute yang dipilih tidak valid.',
    'exists' => ':attribute yang dipilih tidak ditemukan di sistem.',
    'extensions' => 'Berkas :attribute harus berekstensi salah satu dari: :values.',
    'file' => 'Kolom :attribute harus berupa berkas.',
    'filled' => 'Kolom :attribute wajib diisi.',
    'gt' => [
        'array' => 'Kolom :attribute harus berisi lebih dari :value item.',
        'file' => 'Ukuran berkas :attribute harus lebih besar dari :value kilobyte.',
        'numeric' => 'Kolom :attribute harus lebih besar dari :value.',
        'string' => 'Kolom :attribute harus lebih dari :value karakter.',
    ],
    'gte' => [
        'array' => 'Kolom :attribute harus berisi :value item atau lebih.',
        'file' => 'Ukuran berkas :attribute minimal :value kilobyte.',
        'numeric' => 'Kolom :attribute minimal bernilai :value.',
        'string' => 'Kolom :attribute minimal :value karakter.',
    ],
    'hex_color' => 'Kolom :attribute harus berupa kode warna heksadesimal yang valid.',
    'image' => 'Berkas :attribute harus berupa gambar.',
    'in' => ':attribute yang dipilih tidak valid.',
    'in_array' => 'Kolom :attribute harus ada di dalam :other.',
    'integer' => 'Kolom :attribute harus berupa angka bulat.',
    'ip' => 'Kolom :attribute harus berupa alamat IP yang valid.',
    'ipv4' => 'Kolom :attribute harus berupa alamat IPv4 yang valid.',
    'ipv6' => 'Kolom :attribute harus berupa alamat IPv6 yang valid.',
    'json' => 'Kolom :attribute harus berupa teks JSON yang valid.',
    'list' => 'Kolom :attribute harus berupa daftar.',
    'lowercase' => 'Kolom :attribute harus ditulis dengan huruf kecil.',
    'lt' => [
        'array' => 'Kolom :attribute harus berisi kurang dari :value item.',
        'file' => 'Ukuran berkas :attribute harus lebih kecil dari :value kilobyte.',
        'numeric' => 'Kolom :attribute harus lebih kecil dari :value.',
        'string' => 'Kolom :attribute harus kurang dari :value karakter.',
    ],
    'lte' => [
        'array' => 'Kolom :attribute tidak boleh lebih dari :value item.',
        'file' => 'Ukuran berkas :attribute maksimal :value kilobyte.',
        'numeric' => 'Kolom :attribute maksimal bernilai :value.',
        'string' => 'Kolom :attribute maksimal :value karakter.',
    ],
    'mac_address' => 'Kolom :attribute harus berupa alamat MAC yang valid.',
    'max' => [
        'array' => 'Kolom :attribute tidak boleh berisi lebih dari :max item.',
        'file' => 'Ukuran berkas :attribute tidak boleh lebih dari :max kilobyte.',
        'numeric' => 'Kolom :attribute tidak boleh lebih dari :max.',
        'string' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
    ],
    'max_digits' => 'Kolom :attribute tidak boleh lebih dari :max angka.',
    'mimes' => 'Berkas :attribute harus bertipe: :values.',
    'mimetypes' => 'Berkas :attribute harus bertipe: :values.',
    'min' => [
        'array' => 'Kolom :attribute harus berisi minimal :min item.',
        'file' => 'Ukuran berkas :attribute minimal :min kilobyte.',
        'numeric' => 'Kolom :attribute minimal bernilai :min.',
        'string' => 'Kolom :attribute minimal :min karakter.',
    ],
    'min_digits' => 'Kolom :attribute minimal terdiri dari :min angka.',
    'missing' => 'Kolom :attribute tidak boleh dikirim.',
    'missing_if' => 'Kolom :attribute tidak boleh dikirim bila :other bernilai :value.',
    'missing_unless' => 'Kolom :attribute tidak boleh dikirim kecuali :other bernilai :value.',
    'missing_with' => 'Kolom :attribute tidak boleh dikirim bila ada :values.',
    'missing_with_all' => 'Kolom :attribute tidak boleh dikirim bila ada :values.',
    'multiple_of' => 'Kolom :attribute harus kelipatan :value.',
    'not_in' => ':attribute yang dipilih tidak valid.',
    'not_regex' => 'Format kolom :attribute tidak sesuai.',
    'numeric' => 'Kolom :attribute harus berupa angka.',
    'password' => [
        'letters' => 'Kolom :attribute harus mengandung minimal satu huruf.',
        'mixed' => 'Kolom :attribute harus mengandung huruf besar dan huruf kecil.',
        'numbers' => 'Kolom :attribute harus mengandung minimal satu angka.',
        'symbols' => 'Kolom :attribute harus mengandung minimal satu simbol.',
        'uncompromised' => ':attribute ini pernah bocor di internet. Pilih kata sandi lain yang lebih aman.',
    ],
    'present' => 'Kolom :attribute harus dikirim.',
    'present_if' => 'Kolom :attribute harus dikirim bila :other bernilai :value.',
    'present_unless' => 'Kolom :attribute harus dikirim kecuali :other bernilai :value.',
    'present_with' => 'Kolom :attribute harus dikirim bila ada :values.',
    'present_with_all' => 'Kolom :attribute harus dikirim bila ada :values.',
    'prohibited' => 'Kolom :attribute tidak boleh diisi.',
    'prohibited_if' => 'Kolom :attribute tidak boleh diisi bila :other bernilai :value.',
    'prohibited_if_accepted' => 'Kolom :attribute tidak boleh diisi bila :other disetujui.',
    'prohibited_if_declined' => 'Kolom :attribute tidak boleh diisi bila :other ditolak.',
    'prohibited_unless' => 'Kolom :attribute tidak boleh diisi kecuali :other bernilai :values.',
    'prohibits' => 'Kolom :attribute membuat :other tidak boleh diisi.',
    'regex' => 'Format kolom :attribute tidak sesuai.',
    'required' => 'Kolom :attribute wajib diisi.',
    'required_array_keys' => 'Kolom :attribute harus berisi: :values.',
    'required_if' => 'Kolom :attribute wajib diisi bila :other bernilai :value.',
    'required_if_accepted' => 'Kolom :attribute wajib diisi bila :other disetujui.',
    'required_if_declined' => 'Kolom :attribute wajib diisi bila :other ditolak.',
    'required_unless' => 'Kolom :attribute wajib diisi kecuali :other bernilai :values.',
    'required_with' => 'Kolom :attribute wajib diisi bila :values diisi.',
    'required_with_all' => 'Kolom :attribute wajib diisi bila :values diisi.',
    'required_without' => 'Kolom :attribute wajib diisi bila :values tidak diisi.',
    'required_without_all' => 'Kolom :attribute wajib diisi bila :values sama sekali tidak diisi.',
    'same' => 'Kolom :attribute harus sama dengan :other.',
    'size' => [
        'array' => 'Kolom :attribute harus berisi tepat :size item.',
        'file' => 'Ukuran berkas :attribute harus tepat :size kilobyte.',
        'numeric' => 'Kolom :attribute harus bernilai :size.',
        'string' => 'Kolom :attribute harus terdiri dari :size karakter.',
    ],
    'starts_with' => 'Kolom :attribute harus diawali salah satu dari: :values.',
    'string' => 'Kolom :attribute harus berupa teks.',
    'timezone' => 'Kolom :attribute harus berupa zona waktu yang valid.',
    'unique' => ':attribute ini sudah dipakai. Gunakan yang lain.',
    'uploaded' => 'Berkas :attribute gagal diunggah. Coba ulangi.',
    'uppercase' => 'Kolom :attribute harus ditulis dengan huruf besar.',
    'url' => 'Kolom :attribute harus berupa alamat web yang valid.',
    'ulid' => 'Kolom :attribute harus berupa ULID yang valid.',
    'uuid' => 'Kolom :attribute harus berupa UUID yang valid.',

    /*
    |--------------------------------------------------------------------------
    | Pesan khusus per kolom
    |--------------------------------------------------------------------------
    |
    | Formatnya "nama_kolom.nama_aturan". Kosong dengan sengaja: pesan yang
    | khas satu form lebih mudah ditelusuri kalau ditulis langsung di
    | controller-nya (argumen ke-2 $request->validate()), seperti yang sudah
    | dilakukan di KegiatanSekolahController & TahunAjaranController.
    |
    */

    'custom' => [],

    /*
    |--------------------------------------------------------------------------
    | Nama kolom yang dibaca operator
    |--------------------------------------------------------------------------
    |
    | Mengganti nama teknis kolom dengan istilah yang dipakai sehari-hari di
    | sekolah: "Kolom nis wajib diisi" jadi "Kolom NIS wajib diisi",
    | "tanggal kejadian" jadi "Tanggal Kejadian".
    |
    */

    'attributes' => [
        // Identitas & akun
        'name' => 'Nama',
        'nama' => 'Nama',
        'email' => 'Email',
        'password' => 'Kata Sandi',
        'password_confirmation' => 'Konfirmasi Kata Sandi',
        'role' => 'Peran',
        'nip' => 'NIP',
        'unit_kerja' => 'Unit Kerja',
        'no_hp' => 'No. HP',
        'is_active' => 'Status Aktif',

        // Siswa & orang tua
        'nis' => 'NIS',
        'nisn' => 'NISN',
        'jenis_kelamin' => 'Jenis Kelamin',
        'nama_ortu' => 'Nama Orang Tua',
        'no_hp_ortu' => 'No. HP Orang Tua',
        'no_wa_ortu' => 'No. WhatsApp Orang Tua',
        'siswa_id' => 'Siswa',
        'siswa' => 'Siswa',
        'alamat' => 'Alamat',
        'provinsi' => 'Provinsi',

        // Kelas, mapel, jadwal
        'kelas_id' => 'Kelas',
        'kelas_ids' => 'Kelas',
        'nama_kelas' => 'Nama Kelas',
        'tingkat' => 'Tingkat',
        'wali_kelas_id' => 'Wali Kelas',
        'guru_id' => 'Guru',
        'mata_pelajaran_id' => 'Mata Pelajaran',
        'nama_mapel' => 'Nama Mata Pelajaran',
        'kode' => 'Kode',
        'hari' => 'Hari',
        'jam_mulai' => 'Jam Mulai',
        'jam_selesai' => 'Jam Selesai',
        'jam_pelajaran_id' => 'Jam Pelajaran',

        // Periode
        'tahun_ajaran_id' => 'Tahun Ajaran',
        'tahun_ajaran_sumber_id' => 'Tahun Ajaran Sumber',
        'tahun_ajaran_tujuan' => 'Tahun Ajaran Tujuan',
        'tahun_ajaran_tujuan_id' => 'Tahun Ajaran Tujuan',
        'dari_tahun_ajaran_id' => 'Tahun Ajaran Sumber',
        'semester' => 'Semester',
        'status' => 'Status',

        // Tanggal
        'tanggal' => 'Tanggal',
        'tanggal_mulai' => 'Tanggal Mulai',
        'tanggal_selesai' => 'Tanggal Selesai',
        'tanggal_kejadian' => 'Tanggal Kejadian',
        'tanggal_acara' => 'Tanggal Acara',
        'tanggal_mutasi' => 'Tanggal Perpindahan',
        'tanggal_pelaksanaan' => 'Tanggal Pelaksanaan',
        'tanggal_perbaikan' => 'Tanggal Pelaksanaan Perbaikan',
        'tanggal_pengayaan' => 'Tanggal Pelaksanaan Pengayaan',
        'tanggal_evaluasi_berikutnya' => 'Tanggal Evaluasi Berikutnya',
        'tanggal_gabung' => 'Tanggal Bergabung',
        'waktu_acara' => 'Waktu Acara',

        // Jurnal & absensi
        'materi' => 'Materi',
        'kegiatan' => 'Kegiatan',
        'absensi' => 'Absensi',
        'keterangan' => 'Keterangan',
        'catatan' => 'Catatan',

        // Penilaian
        'lingkup_materi' => 'Lingkup Materi',
        'materi_ajar' => 'Materi Ajar',
        'jumlah_soal' => 'Banyak Soal',
        'sumatif' => 'Nilai Sumatif',
        'bentuk_perbaikan' => 'Bentuk Pelaksanaan Perbaikan',
        'bentuk_pengayaan' => 'Bentuk Pelaksanaan Pengayaan',

        // BK & surat
        'jenis_pelanggaran_id' => 'Jenis Pelanggaran',
        'nama_pelanggaran' => 'Nama Pelanggaran',
        'kategori' => 'Kategori',
        'poin_default' => 'Poin',
        'kronologi' => 'Kronologi',
        'bukti_file' => 'Berkas Bukti',
        'bukti_catatan' => 'Catatan Bukti',
        'kasus_siswa_id' => 'Kasus',
        'jenis_pembinaan' => 'Jenis Pembinaan',
        'catatan_bk' => 'Catatan BK',
        'hasil_pembinaan' => 'Hasil Pembinaan',
        'alasan' => 'Alasan',
        'alasan_pembatalan' => 'Alasan Pembatalan',
        'jumlah' => 'Jumlah',
        'jenis_surat_id' => 'Jenis Surat',
        'kode_jenis' => 'Kode Jenis Surat',
        'nama_jenis' => 'Nama Jenis Surat',
        'nomor_urut' => 'Nomor Urut',
        'surat_id' => 'Surat',
        'isi' => 'Isi Surat',
        'isi_surat' => 'Isi Surat',
        'template_isi' => 'Template Isi',

        // Pengaturan sekolah
        'nama_sekolah' => 'Nama Sekolah',
        'website_sekolah' => 'Website Sekolah',
        'logo_aplikasi' => 'Logo Aplikasi',

        // Import Excel
        'file' => 'Berkas Excel',
    ],

];
