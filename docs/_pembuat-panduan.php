<?php
require __DIR__.'/_Docx.php';

$d = new Docx();

// =====================================================================
$d->sampulJudul('SIM-SPENGA', 'Sistem Informasi Manajemen Sekolah', [
    'SMP Negeri 3 Bumiayu',
    '',
    'BUKU PANDUAN LENGKAP',
    'Pemasangan, Penggunaan, dan Pemeliharaan',
    '',
    'Edisi Agustus 2026',
]);

// =====================================================================
$d->h1('Daftar Isi');
$d->p('Dokumen ini disusun berurutan. Kalau Anda baru pertama kali memakai SIM-SPENGA, ikuti dari Bagian A sampai Bagian C dulu, baru lompat ke bagian sesuai tugas Anda.');

$d->tabel(['Bagian', 'Isi', 'Untuk siapa'], [
    ['A', 'Mengenal SIM-SPENGA & istilah pentingnya', 'Semua pengguna'],
    ['B', 'Pemasangan di server & aktivasi nomor seri', 'Teknisi / Admin'],
    ['C', 'Persiapan awal — urutan yang WAJIB diikuti', 'Admin & Kurikulum'],
    ['D', 'Panduan harian per peran', 'Sesuai peran masing-masing'],
    ['E', 'Pemberitahuan WhatsApp otomatis', 'Admin, Guru, Wali Kelas'],
    ['F', 'Pemilih periode: melihat data semester lampau', 'Semua pengguna'],
    ['G', 'Pergantian semester & tahun ajaran', 'Admin & Kurikulum'],
    ['H', 'Import data lewat Excel', 'Admin & Kurikulum'],
    ['I', 'Bila terjadi masalah — arti tiap pesan', 'Semua pengguna'],
    ['J', 'Keamanan & pemeliharaan', 'Admin'],
    ['K', 'Lampiran: daftar menu per peran', 'Semua pengguna'],
], [10, 60, 30]);

$d->catatan('Ada dokumen kedua untuk urusan database',
    'Mencadangkan, memulihkan, mengosongkan data uji coba, dan merawat database dibahas terpisah di **Panduan Database SIM-SPENGA** — dokumen khusus Admin. Dokumen yang sedang Anda baca ini tentang pemakaian aplikasinya.');

// =====================================================================
$d->h1('Bagian A — Mengenal SIM-SPENGA');

$d->h2('A.1 Apa itu SIM-SPENGA');
$d->p('SIM-SPENGA adalah aplikasi berbasis web untuk mengelola kegiatan akademik SMP Negeri 3 Bumiayu dalam satu tempat: absensi & jurnal mengajar, penilaian sampai nilai rapor, bimbingan konseling, kegiatan sekolah, ekstrakurikuler, dan surat-menyurat BK.');
$d->p('Aplikasi ini dibuka lewat **peramban** (Chrome, Edge, Firefox) — tidak perlu dipasang di tiap komputer. Cukup buka alamat aplikasinya, lalu masuk dengan akun masing-masing.');

$d->h2('A.2 Siapa saja penggunanya');
$d->tabel(['Peran', 'Tugas utama di aplikasi'], [
    ['Admin', 'Menyiapkan seluruh data dasar, mengelola akun pengguna, mengatur tahun ajaran'],
    ['Kurikulum', 'Pemetaan guru mengajar, jadwal pelajaran, memantau pengisian nilai'],
    ['Kepala Sekolah', 'Melihat & mencetak seluruh laporan (tidak mengubah data)'],
    ['Guru Mata Pelajaran', 'Mengisi jurnal & absensi mengajar, mengisi Daftar Nilai'],
    ['Wali Kelas', 'Semua tugas guru mapel, ditambah rekap kelas perwaliannya'],
    ['Guru BK', 'Mencatat kasus, pembinaan, pemanggilan orang tua, dan surat BK'],
    ['Kesiswaan', 'Kegiatan sekolah dan ekstrakurikuler'],
    ['Tata Usaha (TU)', 'Master jenis surat'],
    ['Orang Tua', 'Portal terpisah: melihat nilai, absensi, dan catatan anaknya'],
], [26, 74]);

$d->catatan('Wali kelas bukan peran terpisah',
    'Di aplikasi ini, wali kelas adalah akun berperan **Guru** yang ditugaskan sebagai wali sebuah kelas pada suatu semester. Menu wali kelas muncul otomatis begitu penugasan itu ada, dan hilang sendiri bila penugasannya berpindah ke guru lain.');

$d->h2('A.3 Enam istilah yang wajib dipahami lebih dulu');
$d->p('Salah paham pada istilah-istilah ini adalah penyebab paling sering "data saya kok hilang". Bacalah pelan-pelan.');

$d->tabel(['Istilah', 'Artinya di SIM-SPENGA'], [
    ['Tahun Ajaran', 'Contoh: 2026/2027. Satu tahun ajaran berisi dua semester.'],
    ['Semester', 'Ganjil atau Genap. Di aplikasi ini keduanya benar-benar terpisah.'],
    ['Periode', 'Gabungan keduanya, contoh "2026/2027 — Semester Ganjil". Inilah satuan kerja aplikasi.'],
    ['Periode Aktif', 'Periode yang sedang BERJALAN di sekolah. Ditentukan Admin. Semua pencatatan baru selalu masuk ke sini.'],
    ['Periode Pilihan', 'Periode yang sedang Anda LIHAT, dipilih lewat kotak di kanan atas. Bawaannya sama dengan periode aktif.'],
    ['Terkunci', 'Periode yang sudah ditutup Admin. Datanya hanya bisa dilihat & dicetak, tidak bisa diubah.'],
], [22, 78]);

$d->h3('Aturan emas: semua data milik satu semester');
$d->p('Sejak pembaruan Agustus 2026, **tidak ada satu pun data yang berlaku untuk satu tahun penuh.** Semuanya milik satu semester: mata pelajaran, jam pelajaran, jenis pelanggaran, jenis surat, ekstrakurikuler beserta anggotanya, kelas beserta daftar siswanya, wali kelas, pembina ekstrakurikuler, guru mengajar, jadwal, sampai seluruh nilai dan absensi.');
$d->p('Akibatnya yang menguntungkan: susunan kelas Semester 2 boleh berbeda dari Semester 1, guru boleh berganti tugas di tengah tahun, dan Semester 1 yang sudah lewat **tidak pernah ikut berubah**.');
$d->p('Akibat yang perlu Anda ingat: semester baru berangkat dari keadaan kosong. Tapi Anda tidak perlu mengetik ulang — ada tombol **Salin Data** yang memindahkan seluruh pengaturan semester sebelumnya sekali klik. Caranya di Bagian G.');

// =====================================================================
$d->h1('Bagian B — Pemasangan & Aktivasi');
$d->p('Bagian ini dikerjakan **sekali saja** oleh teknisi atau Admin saat aplikasi pertama dipasang di server sekolah. Pengguna biasa boleh melewati bagian ini.');

$d->h2('B.1 Yang harus tersedia di server');
$d->poin([
    'PHP versi 8.2 atau lebih baru, dengan ekstensi: `gd`, `zip`, `pdo_mysql`, `mbstring`, `openssl`',
    'MySQL 8 atau MariaDB 10.6 ke atas',
    'Composer (pengelola pustaka PHP)',
    'Node.js & NPM — hanya bila Anda perlu membangun ulang tampilan',
]);

$d->h2('B.2 Langkah pemasangan');
$d->langkah([
    'Salin seluruh folder aplikasi ke server, misalnya ke `/var/www/sim-spenga`.',
    'Buka Terminal / Command Prompt, masuk ke folder itu.',
    'Jalankan `composer install --no-dev --optimize-autoloader` untuk memasang pustaka.',
    'Buat database kosong di MySQL, misalnya bernama `sim_spenga`.',
    'Salin berkas `.env.production.example` menjadi `.env`.',
    'Buka `.env`, isi bagian bertanda `<ISI>`: alamat aplikasi, nama database, pengguna, dan kata sandinya.',
    'Jalankan `php artisan key:generate` — ini membuat kunci enkripsi khusus untuk server ini.',
    'Jalankan `php artisan migrate --force` untuk membuat seluruh tabel.',
    'Jalankan `php artisan db:seed --force` bila ingin data awal (jenis pelanggaran, jam pelajaran, dll).',
    'Jalankan `php artisan storage:link` supaya berkas unggahan bisa ditampilkan.',
    'Jalankan `php artisan config:cache && php artisan route:cache && php artisan view:cache`.',
    'Arahkan dokumen root web server ke folder `public/`, **bukan** ke folder aplikasinya.',
]);

$d->catatan('Wajib diperiksa sebelum dipakai sungguhan',
    'Pastikan di `.env` sudah tertulis `APP_DEBUG=false` dan `APP_ENV=production`. Bila `APP_DEBUG` masih `true`, setiap kesalahan akan menampilkan rincian teknis **beserta kata sandi database** kepada siapa pun yang membukanya.');

$d->h2('B.3 Aktivasi nomor seri');
$d->p('SIM-SPENGA hanya berjalan pada pemasangan yang sudah diaktifkan. Saat pertama dibuka, aplikasi menampilkan halaman **Aktivasi**.');

$d->langkah([
    'Buka alamat aplikasi di peramban. Halaman Aktivasi muncul otomatis.',
    'Ketik nomor seri yang Anda terima dari penyedia aplikasi.',
    'Klik **Aktifkan Sekarang**.',
    'Bila benar, Anda langsung diarahkan ke halaman Masuk. Aktivasi cukup sekali.',
]);

$d->p('Huruf besar/kecil dan tanda hubung tidak masalah — yang penting urutan karakternya benar.');

$d->catatan('Simpan nomor seri Anda baik-baik',
    'Nomor seri **tidak disimpan** di dalam aplikasi maupun database — yang tercatat hanya sidiknya, jadi tidak bisa dibaca balik. Bila hilang, tidak ada cara memulihkannya dari dalam aplikasi; Anda harus menghubungi penyedia. Nomor seri diperlukan lagi bila aplikasi dipindah ke server atau nama domain lain.', 'FDE7E9');

$d->h2('B.4 Masuk pertama kali');
$d->langkah([
    'Buka alamat aplikasi, misalnya `https://sim.smpn3bumiayu.sch.id`.',
    'Masukkan **email** dan **kata sandi** akun Admin.',
    'Klik **Masuk**.',
    'Segera ganti kata sandi Admin lewat menu Pengaturan → Kelola Pengguna.',
]);

// =====================================================================
$d->h1('Bagian C — Persiapan Awal (urutan WAJIB)');
$d->p('Urutan berikut **tidak boleh diacak**. Setiap langkah membutuhkan hasil langkah sebelumnya. Contoh: jadwal pelajaran tidak bisa dibuat sebelum ada mata pelajaran, kelas, jam pelajaran, dan pemetaan guru mengajar.');
$d->p('Dashboard Admin menampilkan **daftar periksa** yang mengikuti urutan ini — pakai itu sebagai penuntun, tanda centangnya menyala sendiri saat sebuah langkah selesai.');

$d->h2('Langkah 1 — Pengaturan Sekolah');
$d->p('Menu: **Pengaturan → Pengaturan Sekolah**');
$d->langkah([
    'Isi Nama Sekolah, NPSN, alamat lengkap, dan nama Kepala Sekolah beserta NIP-nya.',
    'Unggah **Logo Aplikasi** — logo ini dipakai di halaman masuk, ikon tab peramban, dan kop surat.',
    'Isi bagian KOP Surat: baris atas (nama dinas), baris bawah (nama sekolah), dan alamat.',
    'Isi Lokasi & Penanda Tangan — nama kota yang tercetak sebelum tanggal pada surat.',
    'Klik **Simpan Pengaturan**.',
]);
$d->p('Data ini muncul di **semua** lembar cetak. Mengisinya lebih dulu membuat semua cetakan langsung rapi.');

$d->h2('Langkah 2 — Kelola Pengguna');
$d->p('Menu: **Pengaturan → Kelola Pengguna**');
$d->langkah([
    'Klik **+ Tambah Pengguna**.',
    'Isi Nama, NIP, Email (dipakai untuk masuk), dan kata sandi awal.',
    'Pilih **Peran**: Guru, Guru BK, Kurikulum, Kesiswaan, TU, Kepala Sekolah, atau Admin.',
    'Ulangi untuk seluruh guru dan staf.',
]);
$d->catatan('Jangan menghapus pengguna, nonaktifkan saja',
    'Guru yang pindah atau pensiun sebaiknya **dinonaktifkan** (matikan tombol Aktif di form Edit), bukan dihapus. Menghapusnya akan ditolak aplikasi bila namanya masih menempel di jadwal, jurnal, atau penugasan — dan itu memang disengaja supaya riwayat sekolah tetap utuh.');

$d->h2('Langkah 3 — Tahun Ajaran');
$d->p('Menu: **Data Master → Tahun Ajaran**');
$d->langkah([
    'Klik **+ Buat Tahun Ajaran [nama]**. Aplikasi otomatis membuat DUA baris sekaligus: Semester Ganjil dan Semester Genap.',
    'Pada baris **Semester Ganjil**, klik **Aktifkan**.',
    'Periksa kolom **Rentang Tanggal**. Bila bertuliskan "otomatis", aplikasi memakai Juli–Desember untuk Ganjil dan Januari–Juni untuk Genap.',
    'Bila kalender sekolah Anda berbeda, klik **Edit** pada baris itu dan isi Tanggal Mulai & Tanggal Selesai.',
]);
$d->catatan('Untuk apa rentang tanggal itu',
    'Rentang tanggal menentukan tanggal yang boleh disimpan pada periode ini (jurnal, absensi, BK, surat, kegiatan) **sekaligus** batas data yang masuk Laporan Akhir Semester. Karena keduanya memakai sumber yang sama, tidak mungkin ada data yang tersimpan tapi tidak muncul di laporan.');

$d->h2('Langkah 4 — Mata Pelajaran');
$d->p('Menu: **Data Master → Mata Pelajaran**');
$d->langkah([
    'Klik **+ Tambah Mata Pelajaran**, isi Kode (misal `MTK`) dan Nama Mata Pelajaran.',
    'Atau lebih cepat: klik **Import Excel**, unduh templatnya, isi, lalu unggah kembali (lihat Bagian H).',
]);

$d->h2('Langkah 5 — Jam Pelajaran');
$d->p('Menu: **Pengaturan → Jam Pelajaran**');
$d->langkah([
    'Pilih tab hari (Senin sampai Sabtu).',
    'Klik **+ Tambah Jam Ke**, isi Jam ke berapa, jam mulai, dan jam selesai.',
    'Setelah satu hari selesai, klik **Salin ke Hari Lain** dan centang hari-hari yang jamnya sama — jauh lebih cepat daripada mengetik ulang.',
]);

$d->h2('Langkah 6 — Data Kelas & Wali Kelas');
$d->p('Menu: **Data Master → Data Kelas**');
$d->langkah([
    'Klik **+ Tambah Kelas**, isi Nama Kelas (misal `7A`), pilih Tingkat, dan pilih Wali Kelas.',
    'Ulangi untuk seluruh kelas, atau pakai Import Excel.',
]);
$d->catatan('Wali kelas berlaku PER SEMESTER',
    'Mengubah wali kelas saat Semester Genap berjalan **hanya** mengubah Semester Genap. Semester Ganjil yang sudah lewat tidak ikut berubah, sehingga rapor dan rekap semester itu tetap menyebut nama yang benar. Keterangan ini juga muncul di bawah form saat Anda mengedit kelas.');

$d->h2('Langkah 7 — Data Siswa');
$d->p('Menu: **Data Master → Data Siswa**');
$d->langkah([
    'Klik **Import Excel** — untuk jumlah siswa banyak ini jauh lebih cepat daripada satu per satu.',
    'Unduh templat, isi kolom: `nis`, `nisn`, `nama`, `nama_ortu`, `no_wa_ortu`, `jenis_kelamin` (L/P), dan `kode_kelas`.',
    'Kolom `kode_kelas` diisi nama kelas persis seperti di menu Data Kelas, misalnya `7A`.',
    'Unggah berkasnya. Aplikasi melaporkan berapa baris masuk dan baris mana yang dilewati beserta alasannya.',
]);

$d->h2('Langkah 8 — Akun Portal Orang Tua');
$d->p('Menu: **Data Master → Data Siswa** (tombol di bagian atas)');
$d->langkah([
    'Klik **Buatkan Akun Ortu** untuk membuat akun bagi semua siswa yang belum punya.',
    'Nama pengguna portal adalah **NIS anak**, kata sandi awalnya `password`.',
    'Bagikan informasi itu kepada orang tua.',
]);
$d->catatan('Orang tua WAJIB mengganti kata sandi saat pertama masuk',
    'Karena nama penggunanya adalah NIS — nomor yang tercetak di rapor dan diketahui teman sekelas — aplikasi **memaksa** orang tua mengganti kata sandi sebelum portal bisa dibuka. Selama belum diganti, seluruh halaman portal dialihkan ke form ganti kata sandi. Kolom "Akun Ortu" di Data Siswa menunjukkan mana yang sudah dan belum mengganti.', 'FDE7E9');

$d->h2('Langkah 9 — Pemetaan Guru Mengajar');
$d->p('Menu: **Data Master → Pemetaan Guru Mengajar**');
$d->p('Di sinilah ditentukan **siapa mengajar apa di kelas mana**. Langkah ini wajib sebelum jadwal bisa dibuat, dan menentukan guru mana yang berhak mengisi Daftar Nilai suatu mapel.');
$d->langkah([
    'Pilih Guru, Kelas, dan Mata Pelajaran, lalu klik Simpan.',
    'Seorang guru boleh mengampu lebih dari satu mata pelajaran di kelas yang sama — tidak masalah.',
]);

$d->h2('Langkah 10 — Pemetaan Guru BK');
$d->p('Menu: **Data Master → Pemetaan Guru BK**');
$d->p('Menentukan kelas mana yang menjadi binaan tiap Guru BK. Guru BK hanya bisa melihat dan mencatat data siswa di kelas binaannya.');

$d->h2('Langkah 11 — Jadwal Pelajaran');
$d->p('Menu: **Data Master → Jadwal Pelajaran**');
$d->langkah([
    'Pilih Hari, Kelas, Jam Pelajaran, Mata Pelajaran, dan Guru.',
    'Klik Simpan.',
]);
$d->p('Aplikasi menolak dua keadaan secara otomatis: satu kelas punya dua pelajaran di jam yang sama, dan satu guru dijadwalkan di dua kelas pada jam yang sama.');
$d->p('Bila muncul pesan *"Guru tersebut tidak terdaftar mengajar mapel ini di kelas ini"*, berarti Langkah 9 untuk kombinasi itu belum dibuat.');

$d->h2('Langkah 12 — Pengaturan Penilaian');
$d->p('Menu: **Pengaturan → Pengaturan Penilaian**');
$d->langkah([
    'Isi **bobot** nilai rapor: Formatif, Sumatif Lingkup Materi, dan Sumatif Akhir. Bawaannya 60 / 20 / 20 dan totalnya harus 100.',
    'Isi **KKTP** (Kriteria Ketercapaian Tujuan Pembelajaran) untuk tiap tingkat, biasanya antara 73 dan 82.',
    'Pilih **kebijakan remedial** — cara nilai remedi diperhitungkan.',
]);

$d->h2('Langkah 13 — Data pendukung lain');
$d->tabel(['Menu', 'Isi', 'Diisi oleh'], [
    ['Pengaturan → Jenis Pelanggaran', 'Daftar pelanggaran beserta kategori & poinnya', 'Guru BK / Admin'],
    ['Administrasi Surat → Jenis Surat', 'Jenis surat BK beserta templat isinya', 'TU / Admin'],
    ['Kesiswaan → Ekstrakurikuler', 'Kegiatan ekskul, pembina, dan anggotanya', 'Kesiswaan'],
    ['Kesiswaan → Kegiatan Sekolah', 'Kegiatan yang absensinya dicatat wali kelas', 'Kesiswaan'],
], [34, 44, 22]);

// =====================================================================
$d->h1('Bagian D — Panduan Harian per Peran');

$d->h2('D.1 Guru Mata Pelajaran');

$d->h3('Mengisi jurnal & absensi mengajar');
$d->p('Menu: **Kegiatan Mengajar → Absensi & Jurnal Mengajar**');
$d->langkah([
    'Halaman menampilkan jadwal mengajar Anda hari ini. Klik sesi yang akan diisi.',
    'Periksa **tanggal** di bagian atas. Bawaannya hari ini; ubah bila mengisi tanggal yang terlewat.',
    'Isi **Materi** dan **Kegiatan** pembelajaran.',
    'Tandai kehadiran tiap siswa: Hadir, Sakit, Izin, atau Alfa.',
    'Klik **Simpan**.',
]);
$d->catatan('Mengisi tanggal yang terlewat itu aman',
    'Daftar siswa yang muncul adalah anggota kelas **pada tanggal itu**, bukan hari ini. Jadi bila ada siswa yang pindah kelas minggu lalu, ia tetap muncul saat Anda mengisi absensi tanggal sebelum kepindahannya.');

$d->h3('Mengisi Daftar Nilai');
$d->p('Menu: **Penilaian → Daftar Nilai**');
$d->langkah([
    'Pilih kelas dan mata pelajaran yang Anda ampu.',
    'Isi kolom **TPF 1–7** untuk nilai formatif.',
    'Isi **LM 1–4**: kolom SUM untuk nilai sumatif lingkup materi, kolom REM untuk nilai remedi bila ada.',
    'Isi **ASTS** (Asesmen Sumatif Tengah Semester) dan **ASAS/ASAT** (akhir semester/tahun).',
    'Nilai Akhir dan predikatnya dihitung otomatis sambil Anda mengetik — tidak perlu menghitung sendiri.',
    'Klik **Simpan**.',
    'Bila seluruh nilai sudah final, klik **Finalisasi**. Setelah difinalisasi, lembar terkunci.',
]);
$d->p('Bila ternyata masih ada yang perlu diperbaiki setelah finalisasi, mintalah Kurikulum membuka kuncinya lewat tombol **Buka Kunci**.');

$d->h3('Analisis & Program Perbaikan');
$d->poin([
    '**Analisis Hasil Tes Sumatif** — dibuka dari Daftar Nilai. Isi Materi Ajar, banyak butir soal, dan tanggal pelaksanaan; rincian skor per butir soal dihitung otomatis dari nilai SUM yang sudah Anda masukkan.',
    '**Program Pengayaan & Perbaikan** — bentuk dan tanggal pelaksanaannya diisi di sini, daftar siswanya diambil otomatis dari hasil analisis.',
]);

$d->h2('D.2 Wali Kelas');
$d->p('Selain semua tugas guru mata pelajaran, wali kelas punya lima lembar tambahan yang muncul otomatis di menu:');
$d->tabel(['Menu', 'Isinya'], [
    ['Penilaian → Nilai Rapor Kelas', 'Seluruh mapel dalam satu tabel: baris siswa, kolom mapel, plus rata-rata & peringkat'],
    ['Penilaian → Nilai per Mata Pelajaran', 'Rincian formatif sampai nilai akhir untuk satu mapel'],
    ['Penilaian → Laporan Akhir Semester', 'Rekap satu semester penuh: nilai, kehadiran, kedisiplinan, ekstrakurikuler'],
    ['Monitoring → Rekap Absensi Kelas', 'Kehadiran bulanan kelas perwalian'],
    ['Monitoring → Jurnal Mengajar Kelas', 'Jurnal semua guru yang mengajar di kelas ini'],
], [38, 62]);
$d->p('**Laporan Akhir Semester** adalah lembar yang dipakai saat rapat penerimaan rapor — menggabungkan empat hal yang dulu harus dibuka di empat menu berbeda.');
$d->p('Semua lembar punya tombol **Cetak**. Kop surat, nama & NIP wali kelas, serta kepala sekolah terisi otomatis dari Pengaturan Sekolah.');

$d->h2('D.3 Guru BK');
$d->p('Menu: **Kesiswaan → Bimbingan Konseling**. Seluruh pencatatan BK berpangkal dari sini, terbagi dalam lima tab:');
$d->tabel(['Tab', 'Untuk mencatat'], [
    ['Kasus', 'Pelanggaran siswa beserta kronologi dan berkas bukti'],
    ['Pembinaan', 'Tindakan pembinaan atas sebuah kasus'],
    ['Pengurangan Poin', 'Pengurangan poin pelanggaran karena perbaikan perilaku'],
    ['Pemanggilan', 'Pemanggilan orang tua beserta hasil pertemuannya'],
    ['Laporan Bulanan', 'Rekap sebulan siap cetak untuk Kepala Sekolah'],
], [26, 74]);

$d->h3('Mencatat kasus');
$d->langkah([
    'Buka tab **Kasus**, klik **+ Catat Kasus**.',
    'Cari dan pilih siswanya.',
    'Isi tanggal kejadian (tidak boleh melewati hari ini).',
    'Pilih **Jenis Pelanggaran** — kategori dan poin terisi otomatis dari master, tidak bisa diketik manual.',
    'Isi kronologi minimal 10 huruf. Lampirkan bukti bila ada (JPG, PNG, atau PDF, maksimal 5 MB).',
    'Klik Simpan.',
]);
$d->p('Untuk mengubah status kasus, cukup klik tombol **Tandai Selesai** pada barisnya. Kasus yang salah input **dibatalkan**, bukan dihapus — riwayatnya tetap tersimpan beserta alasan pembatalannya.');

$d->h3('Membuat surat BK');
$d->p('Menu: **Administrasi Surat → Surat BK**');
$d->langkah([
    'Klik **+ Buat Surat**, pilih jenis suratnya.',
    'Cari dan pilih siswa.',
    'Isi tanggal dan **Nomor Urut** (angka saja, misalnya `12`).',
    'Nomor surat lengkap disusun otomatis, termasuk bulan dalam angka Romawi.',
    'Klik Simpan, lalu **Cetak**.',
]);
$d->p('Aplikasi menolak nomor surat yang sudah pernah dipakai, sehingga tidak akan ada dua surat bernomor sama.');

$d->h2('D.4 Kesiswaan');
$d->h3('Kegiatan Sekolah');
$d->langkah([
    'Menu **Kesiswaan → Kegiatan Sekolah**, klik **+ Tambah Kegiatan**.',
    'Isi nama, jenis, tanggal mulai & selesai (harus di dalam rentang periode berjalan).',
    'Pilih cakupan: semua kelas, satu tingkat, atau kelas tertentu.',
    'Setelah disimpan, wali kelas dapat mengisi absensi kegiatan itu.',
]);

$d->h3('Ekstrakurikuler');
$d->langkah([
    'Menu **Kesiswaan → Ekstrakurikuler**, klik **+ Tambah Kegiatan**.',
    'Isi namanya, lalu centang **Pembina** dari daftar guru. Pembina dari luar sekolah diketik bebas.',
    'Klik **Anggota** pada barisnya untuk mengisi daftar siswa — bisa per kelas sekaligus lewat daftar centang.',
]);
$d->catatan('Pembina berlaku per semester',
    'Bila pembina Pramuka berganti di Semester Genap, ubah saja di semester itu — Semester Ganjil tetap mencatat pembina yang lama, sehingga rekap absensi ekskul semester lalu tetap menyebut nama yang benar.');

$d->h3('Menghentikan atau menghapus sebuah kegiatan');
$d->p('Dua hal yang sering tertukar. Pilih yang sesuai keadaannya:');
$d->tabel(['Keadaan', 'Yang benar dilakukan', 'Akibatnya'], [
    [
        'Kegiatan **berhenti berjalan** (peminat habis, pembina pensiun)',
        'Klik ikon pensil → hilangkan centang **Aktif** → **Simpan**',
        'Riwayat absensi & nilai ekskul tetap utuh untuk rapor; kegiatan tidak lagi muncul sebagai pilihan baru',
    ],
    [
        'Kegiatan **salah dibuat**, belum ada anggota maupun absensi',
        'Klik ikon tong sampah',
        'Langsung terhapus',
    ],
    [
        'Kegiatan **salah dibuat**, tetapi terlanjur ada anggota atau absensi',
        'Klik ikon pensil → gulir ke bagian merah **Hapus Kegiatan** → **Hapus permanen beserta datanya**',
        'Anggota, pembina, dan seluruh sesi absensinya ikut terhapus dan tidak bisa dikembalikan',
    ],
], [30, 34, 36]);

$d->catatan('Tombol tong sampah menolak? Itu memang pengamannya',
    'Kegiatan yang sudah punya anggota atau pernah sekali saja diabsen tidak bisa dihapus dengan tombol biasa, dan pesan penolakannya menyebut angkanya — mis. "masih dipakai 6 anggota ekstrakurikuler dan 1 sesi absensi ekstrakurikuler". Tanpa pengaman itu, satu klik bisa melenyapkan riwayat kehadiran satu semester. Untuk kegiatan yang benar-benar salah dibuat, pakai tombol **Hapus permanen** di panel Edit.');

$d->catatan('Kegiatan bernama sama di semester lain tidak ikut terhapus',
    'Karena ekstrakurikuler tercatat per semester, "Pramuka" Semester Ganjil dan "Pramuka" Semester Genap adalah dua data yang berbeda. Menghapus salah satunya tidak menyentuh yang lain.');

$d->h2('D.5 Kurikulum');
$d->poin([
    '**Monitoring Input Nilai** — melihat mapel dan kelas mana yang nilainya belum lengkap, satu layar untuk seluruh sekolah.',
    '**Kehadiran Mengajar Guru** — memantau guru yang belum mengisi jurnal.',
    '**Rekapitulasi Kepatuhan** — rekap kepatuhan pengisian.',
    '**Buka Kunci** daftar nilai yang sudah difinalisasi guru bila masih perlu diperbaiki.',
]);

$d->h2('D.6 Kepala Sekolah');
$d->p('Seluruh menu Kepala Sekolah bersifat **lihat dan cetak saja**. Tidak ada tombol simpan atau hapus, sehingga tidak mungkin mengubah data tanpa sengaja.');

$d->h2('D.7 Orang Tua');
$d->langkah([
    'Buka alamat aplikasi lalu tambahkan `/orangtua/login`, atau klik tautan Portal Orang Tua di halaman masuk.',
    'Masukkan **NIS anak** sebagai nama pengguna dan kata sandi yang diberikan sekolah.',
    'Saat pertama masuk, aplikasi meminta Anda mengganti kata sandi. Ini wajib demi keamanan data anak.',
    'Setelah itu dashboard menampilkan nilai, kehadiran, catatan BK, dan riwayat kelas anak Anda.',
]);

// =====================================================================
$d->h1('Bagian E — Pemberitahuan WhatsApp Otomatis');

$d->p('SIM-SPENGA mengirim dua macam pesan WhatsApp, dan keduanya berjalan sendiri tanpa ada yang perlu menekan tombol. Keduanya sengaja dikirim dari **nomor yang berbeda**.');

$d->h2('E.1 Dua nomor pengirim yang terpisah');

$d->tabel(['', 'Pemberitahuan Alfa', 'Pengingat Jurnal & Absensi'], [
    ['Dikirim dari', 'Nomor **sekolah**', 'Nomor **kepala sekolah**'],
    ['Dikirim kepada', 'Orang tua/wali siswa', 'Guru yang bersangkutan'],
    ['Isinya', 'Anak Bapak/Ibu tercatat Alfa hari ini', 'Jurnal & absensi Anda belum terisi'],
    ['Kapan', 'Segera setelah guru menyimpan absensi', '30 menit setelah jam pelajaran selesai'],
    ['Diatur di', 'Berkas `.env` di server', 'Menu Pengaturan → Pengingat Guru (WA)'],
], [22, 39, 39]);

$d->catatan('Kenapa harus dua nomor',
    'Supaya percakapan sekolah dengan orang tua tidak bercampur dengan pengingat internal kepada guru dalam satu nomor. Guru juga lebih menanggapi pengingat yang datang dari nomor kepala sekolah. Di layanan pengirim WhatsApp (Fonnte), keduanya adalah **perangkat** yang berbeda dalam satu akun — yang membedakan hanya tokennya.');

$d->h2('E.2 Pemberitahuan Alfa ke orang tua');

$d->p('Begitu guru menyimpan absensi yang berisi status **Alfa**, aplikasi menyusun pesan ke nomor WhatsApp orang tua siswa tersebut. Pesannya menyebut tanggal, nama siswa, dan mata pelajaran atau nama kegiatan.');

$d->poin([
    'Nomor tujuan diambil dari kolom **No. WA Orang Tua** di Data Siswa. Bila kosong, pengiriman ditandai gagal beserta alasannya.',
    'Bila nomornya ditolak, aplikasi mencoba sekali lagi. Gagal juga berarti berhenti — kemungkinan besar nomor itu memang bukan WhatsApp aktif.',
    'Riwayat lengkapnya ada di menu **Monitoring → Notifikasi WhatsApp Ortu**, bisa disaring per bulan.',
]);

$d->h2('E.3 Pengingat jurnal & absensi ke guru');

$d->p('Setiap 5 menit, aplikasi memeriksa jadwal hari itu. Sesi mengajar yang jurnal & absensinya belum terisi **30 menit setelah jam pelajarannya berakhir** akan dikirimi pengingat ke nomor WhatsApp gurunya.');

$d->tabel(['Aturan', 'Penjelasan'], [
    ['Dihitung dari jam **terakhir**', 'Guru yang mengajar 3 jam berturut-turut baru diingatkan 30 menit setelah jam ketiganya usai — bukan di tengah ia masih mengajar.'],
    ['Satu pesan per **sesi**', 'Tiga jam berurutan di kelas & mata pelajaran yang sama dihitung satu sesi, persis seperti satu baris yang guru klik di layar. Jadi satu pesan, bukan tiga.'],
    ['Tidak pernah dikirim dua kali', 'Sesi yang sudah diingatkan tidak akan diingatkan lagi pada hari yang sama.'],
    ['Keburu diisi = tidak jadi dikirim', 'Bila guru mengisi jurnalnya tepat saat pesan masih menunggu giliran kirim, pesannya dibatalkan dan ditandai **Dilewati**.'],
    ['Ada jam kirim', 'Di luar jendela jam yang diatur Admin (mis. 06.30–18.00), pengiriman ditunda — guru tidak dihubungi larut malam.'],
], [30, 70]);

$d->h3('Contoh pesan yang diterima guru');
$d->kode("Assalamu'alaikum, Bapak/Ibu Budi Santoso, S.Pd.\n\nSistem mencatat jurnal mengajar & absensi berikut belum terisi:\n\nHari/Tanggal : Jumat, 14 Agustus 2026\nKelas : *7A*\nMata pelajaran : *Bahasa Indonesia*\nJam ke : *2-3* (07.40-08.20)\n\nMohon segera diisi melalui menu \"Absensi & Jurnal Mengajar\".\n\n_Pesan otomatis dari SMP Negeri 3 Bumiayu. Bila jurnal sudah diisi, abaikan pesan ini._");

$d->h2('E.4 Menyiapkannya — untuk Admin');

$d->p('Menu **Pengaturan → Pengingat Guru (WA)**. Menu ini hanya terbuka untuk Admin, karena memegang token perangkat WhatsApp kepala sekolah.');

$d->langkah([
    'Pastikan **nomor WhatsApp setiap guru sudah terisi** di menu Kelola Pengguna. Tanpa itu pengingatnya tercatat gagal.',
    'Buka dasbor Fonnte → menu **Device** → pilih perangkat bernomor kepala sekolah → salin **Token**-nya.',
    'Tempelkan token itu pada kolom **Token perangkat WhatsApp kepala sekolah**, lalu klik **Simpan Pengaturan**.',
    'Isi sebuah nomor di kotak **Uji Coba Pengiriman**, klik **Kirim Pesan Uji**, dan pastikan pesannya benar-benar sampai. Lakukan ini SEBELUM menyalakan pengingat untuk seluruh guru.',
    'Bila pesan uji sampai, centang **Nyalakan pengingat otomatis** lalu Simpan.',
    'Atur **jeda** (bawaan 30 menit) dan **jendela jam kirim** sesuai kebiasaan sekolah.',
]);

$d->catatan('Token tidak akan terhapus tanpa sengaja',
    'Setelah disimpan, token tidak pernah ditampilkan lagi dan kolomnya selalu terlihat kosong. Menyimpan halaman dengan kolom itu kosong **tidak menghapus** token yang sudah ada — jadi Admin bisa mengubah jeda tanpa takut merusak sambungan. Untuk benar-benar mengganti perangkat, ada tombol **Hapus token perangkat yang tersimpan**.');

$d->h3('Mengubah naskah pesannya');
$d->p('Naskah pesan boleh diubah Admin. Kata kunci di bawah ini akan diganti dengan data yang sesungguhnya saat pesan dikirim:');
$d->tabel(['Kata kunci', 'Diganti menjadi'], [
    ['`{guru}`', 'Nama guru yang diingatkan'],
    ['`{tanggal}`', 'Hari dan tanggal, mis. Senin, 01 September 2026'],
    ['`{kelas}`', 'Nama kelas, mis. 7A'],
    ['`{mapel}`', 'Nama mata pelajaran'],
    ['`{jam}`', 'Jam ke berapa, mis. 3-4'],
    ['`{waktu}`', 'Rentang pukulnya, mis. 08.20-09.40'],
    ['`{sekolah}`', 'Nama sekolah dari Pengaturan Sekolah'],
    ['`{aplikasi}`', 'Alamat aplikasi'],
], [22, 78]);
$d->p('Kosongkan kolom naskah untuk kembali memakai naskah bawaan.');

$d->h2('E.5 Membaca riwayat pengiriman');

$d->p('Di bawah halaman Pengaturan ada riwayat per bulan beserta ringkasan jumlahnya. Arti tiap status:');

$d->tabel(['Status', 'Artinya', 'Yang perlu dilakukan'], [
    ['Terkirim', 'Pesan diterima layanan WhatsApp', 'Tidak ada'],
    ['Menunggu', 'Sudah tercatat, pesannya masih dalam antrian', 'Tunggu sebentar. Bila menetap lama, pekerja antrian di server kemungkinan mati'],
    ['Dilewati', 'Guru keburu mengisi jurnalnya, atau pengingat dimatikan sebelum terkirim', 'Tidak ada — ini justru hasil yang diinginkan'],
    ['Gagal', 'Nomor guru bermasalah, atau token salah', 'Perbaiki nomornya di Kelola Pengguna, lalu klik **Kirim ulang** pada barisnya'],
], [16, 44, 40]);

$d->h2('E.6 Bila pengingat tidak jalan sama sekali');

$d->p('Pengingat dikerjakan di belakang layar, jadi **dua proses harus hidup terus** di server. Bila salah satunya mati, pengaturan tetap tersimpan tetapi tidak ada pesan yang keluar sama sekali. Cara memasangnya ada di **Panduan Database SIM-SPENGA**.');

$d->tabel(['Gejala', 'Penyebab yang paling mungkin'], [
    ['Riwayat kosong sama sekali, padahal ada jurnal yang belum diisi', 'Penjadwal (`schedule:run`) tidak berjalan, atau saklarnya belum dinyalakan'],
    ['Riwayat terisi tetapi semuanya berstatus **Menunggu**', 'Pekerja antrian (`queue:work`) tidak berjalan'],
    ['Semua berstatus **Gagal** dengan keterangan nomor', 'Nomor WhatsApp guru belum diisi di Kelola Pengguna'],
    ['Semua **Gagal** dengan keterangan token', 'Token perangkat salah atau perangkatnya terputus di Fonnte'],
    ['Halaman Pengaturan menampilkan kotak kuning', 'Pengingat dinyalakan tetapi tokennya belum diisi'],
], [45, 55]);

// =====================================================================
$d->h1('Bagian F — Melihat Data Semester Lampau');
$d->p('Di kanan atas setiap halaman ada **kotak pemilih periode**. Isinya seluruh periode yang pernah ada.');

$d->h2('F.1 Cara memakainya');
$d->langkah([
    'Klik kotak periode di kanan atas.',
    'Pilih periode yang ingin dilihat, misalnya "2026/2027 Ganjil".',
    'Halaman langsung menampilkan keadaan periode itu.',
]);

$d->h2('F.2 Yang berubah saat Anda menengok periode lampau');
$d->poin([
    'Kotak periodenya berubah warna menjadi kuning.',
    'Muncul spanduk **"Mode lihat saja"** di setiap halaman.',
    'Semua tombol simpan tidak berfungsi — data periode lampau tidak bisa diubah.',
    '**Peran Anda ikut menyesuaikan periode itu.** Bila dulu Anda wali kelas 7A, menu wali kelas muncul kembali beserta daftar siswa dan nilai kelas itu apa adanya.',
]);

$d->catatan('Inilah jawaban "data lama saya ke mana?"',
    'Guru yang tidak lagi menjadi wali kelas di semester berjalan tetap bisa membuka seluruh rekap, absensi, dan jurnal kelas perwaliannya di semester lampau — cukup pilih periodenya. Datanya tidak pernah hilang, hanya perlu dilihat dari periode yang benar.', 'E7F3E7');

$d->h2('F.3 Kembali ke periode berjalan');
$d->p('Pilih kembali periode yang bertanda **(berjalan)** di kotak yang sama. Spanduk kuning hilang dan tombol simpan berfungsi lagi.');

// =====================================================================
$d->h1('Bagian G — Pergantian Semester & Tahun Ajaran');

$d->h2('G.1 Pergantian Semester (Ganjil ke Genap)');
$d->p('Dikerjakan Admin di menu **Data Master → Tahun Ajaran**.');
$d->langkah([
    'Pastikan seluruh nilai Semester Ganjil sudah difinalisasi guru — cek lewat Monitoring Input Nilai.',
    'Pada baris **Semester Ganjil**, klik **Tutup Semester**. Datanya menjadi hanya-baca dan tidak bisa diubah lagi.',
    'Pada baris **Semester Genap**, klik **Aktifkan**.',
    'Pada baris **Semester Ganjil**, klik **Salin Data**.',
    'Pilih tujuan **Semester Genap**, lalu periksa halaman Pratinjau. Halaman itu menampilkan persis apa saja yang akan disalin, dan belum menyimpan apa pun.',
    'Klik **Salin Sekarang**.',
    'Buka Data Kelas → sesuaikan wali kelas yang berganti. Periksa juga Pemetaan Guru Mengajar dan Jadwal.',
]);

$d->p('Yang ikut tersalin antar semester:');
$d->tabel(['Kategori', 'Ikut disalin?'], [
    ['Mata Pelajaran, Jam Pelajaran, Jenis Pelanggaran, Jenis Surat', 'Ya'],
    ['Ekstrakurikuler beserta pembina dan anggotanya', 'Ya'],
    ['Kelas beserta daftar siswanya', 'Ya'],
    ['Penugasan Wali Kelas', 'Ya — tinggal ubah yang berganti'],
    ['Pemetaan Guru Mengajar, Guru BK, Jadwal Pelajaran', 'Ya'],
    ['Nilai, absensi, jurnal, kasus BK, surat', 'Tidak — itu catatan kejadian milik semesternya sendiri'],
], [58, 42]);

$d->h2('G.2 Pergantian Tahun Ajaran');
$d->langkah([
    'Pastikan **kedua** semester tahun lama sudah ditutup. Aplikasi menolak mengaktifkan tahun baru bila masih ada semester lama yang terbuka.',
    'Klik **+ Buat Tahun Ajaran [nama]** — Semester Ganjil & Genap tahun baru terbuat sekaligus.',
    'Pada baris **Semester Genap tahun LAMA**, klik **Salin Data** → tujuan **Semester Ganjil tahun BARU** → periksa Pratinjau → **Salin Sekarang**.',
    'Klik **Aktifkan** pada Semester Ganjil tahun baru. Mulai saat itu seluruh menu menampilkan data tahun baru.',
    'Buka Data Kelas → sesuaikan wali kelas. Periksa Guru Mengajar & Jadwal.',
    'Buka **Data Siswa → Import Excel** dengan `kode_kelas` kelas barunya: kelas 7 lama menjadi 8, kelas 8 lama menjadi 9, ditambah siswa baru kelas 7.',
    'Buka Ekstrakurikuler → isi kembali daftar anggotanya.',
]);

$d->catatan('Siswa kelas 9 yang lulus tidak perlu dinonaktifkan satu per satu',
    'Cukup tidak diikutkan pada berkas import di langkah 6. Karena setiap menu hanya menampilkan siswa yang kelasnya milik periode aktif, mereka otomatis berhenti muncul — sementara seluruh riwayat, nilai, dan catatan BK-nya tetap tersimpan dan bisa dibuka kapan saja lewat pemilih periode.', 'E7F3E7');

// =====================================================================
$d->h1('Bagian H — Import Data lewat Excel');
$d->p('Lima menu menyediakan import: Mata Pelajaran, Data Kelas, Data Siswa, Pemetaan Guru Mengajar, dan Jadwal Pelajaran. Cara pakainya sama persis.');

$d->h2('H.1 Langkah umum');
$d->langkah([
    'Buka menu yang bersangkutan, klik tombol **Import Excel**.',
    'Klik **Unduh Templat**. Jangan membuat berkas sendiri dari nol.',
    'Isi templat itu **tanpa mengubah baris judul di baris pertama**.',
    'Simpan sebagai `.xlsx` atau `.csv`, ukuran maksimal 10 MB.',
    'Kembali ke halaman import, pilih berkasnya, klik **Import**.',
    'Baca panel hasilnya: berapa baris dibuat, berapa diperbarui, dan baris mana yang dilewati beserta alasannya.',
]);

$d->h2('H.2 Kolom tiap templat');
$d->tabel(['Menu', 'Kolom wajib'], [
    ['Mata Pelajaran', '`kode`, `nama_mapel`'],
    ['Data Kelas', '`nama_kelas`, `tingkat` — `nip_wali_kelas` opsional'],
    ['Data Siswa', '`nis`, `nama`, `kode_kelas` (kolom lain opsional)'],
    ['Guru Mengajar', '`nip_guru`, `kode_kelas`, `kode_mapel`'],
    ['Jadwal Pelajaran', '`hari`, `kode_kelas`, `jam_ke`, `kode_mapel`, `nip_guru`'],
], [30, 70]);

$d->p('Kolom `kode_kelas` diisi **nama kelas** persis seperti tertulis di menu Data Kelas, misalnya `7A`. Kolom `nip_guru` diisi NIP persis seperti di menu Kelola Pengguna, dan `kode_mapel` diisi kode dari menu Mata Pelajaran.');

$d->h2('H.3 Hal penting tentang import');
$d->poin([
    'Import **aman diulang**. Baris yang sudah ada akan diperbarui, bukan digandakan.',
    'Import selalu masuk ke **periode yang sedang berjalan**, tidak pernah ke periode lain.',
    'Untuk Data Siswa, NIS yang sudah ada akan **dipindahkan** ke kelas yang tertulis — inilah cara menaikkan kelas massal. Riwayat kelasnya tercatat otomatis.',
    'Baris yang bermasalah dilewati dan dilaporkan lengkap dengan **nomor barisnya di Excel**, sehingga mudah diperbaiki.',
]);

// =====================================================================
$d->h1('Bagian I — Bila Terjadi Masalah');
$d->p('Aplikasi ini sengaja menjelaskan sebab penolakan, bukan sekadar menolak. Berikut pesan yang paling sering muncul dan artinya.');

$d->tabel(['Pesan', 'Artinya', 'Yang harus dilakukan'], [
    ['Tanggal berada di luar [periode]', 'Tanggal yang diisi bukan milik semester yang berjalan', 'Perbaiki tanggalnya, atau minta Admin mengaktifkan periode yang sesuai'],
    ['Mode lihat saja', 'Anda sedang menengok periode lampau', 'Kembali ke periode berjalan lewat kotak di kanan atas'],
    ['Periode ini sudah ditutup dan terkunci', 'Semester sudah ditutup Admin', 'Hubungi Admin bila benar-benar perlu dibuka kembali'],
    ['… tidak dapat dihapus — masih dipakai …', 'Data masih menjadi rujukan data lain', 'Pesan menyebut jumlah & jenisnya; bereskan dulu, atau nonaktifkan saja'],
    ['Guru tersebut tidak terdaftar mengajar mapel ini', 'Pemetaan Guru Mengajar belum dibuat', 'Buat dulu pemetaannya (Langkah 9)'],
    ['Kelas ini sudah punya jadwal lain di jam yang sama', 'Bentrok jadwal kelas atau guru', 'Pilih jam atau kelas lain'],
    ['Kelas [X] milik [periode lain]', 'Tautan menunjuk kelas dari semester berbeda', 'Ganti periode di kanan atas'],
    ['Belum ada mata pelajaran yang dipetakan', 'Kelas ini belum punya pemetaan guru mengajar pada periode ini', 'Lengkapi lewat Pemetaan Guru Mengajar'],
    ['Terlalu banyak percobaan masuk', 'Salah kata sandi lebih dari 5 kali dalam semenit', 'Tunggu satu menit, lalu coba lagi'],
    ['Aplikasi belum diaktifkan', 'Nomor seri belum dimasukkan di server ini', 'Masukkan nomor seri di halaman Aktivasi'],
    ['Nomor WhatsApp guru belum diisi', 'Pengingat jurnal tidak punya nomor tujuan', 'Isi kolom No. HP guru di menu Kelola Pengguna'],
    ['Token perangkat WhatsApp kepala sekolah belum diisi', 'Pengingat dinyalakan tanpa token', 'Isi tokennya di Pengaturan → Pengingat Guru (WA)'],
], [30, 34, 36]);

$d->h2('I.1 Bila halaman tidak mau terbuka sama sekali');
$d->langkah([
    'Periksa apakah Anda sudah masuk. Bila sesi kedaluwarsa, aplikasi mengarahkan ke halaman masuk.',
    'Periksa kotak periode di kanan atas — mungkin Anda sedang menengok periode yang datanya memang belum ada.',
    'Bila muncul halaman Aktivasi, berarti aplikasi belum diaktifkan di server itu.',
    'Bila masih bermasalah, hubungi Admin dengan menyebutkan **menu apa**, **peran akun Anda**, dan **pesan yang muncul**.',
]);

// =====================================================================
$d->h1('Bagian J — Keamanan & Pemeliharaan');

$d->h2('J.1 Kebiasaan yang wajib dijaga');
$d->poin([
    'Setiap orang memakai **akunnya sendiri**. Jangan pernah berbagi akun — seluruh catatan aplikasi menyimpan siapa yang mengerjakan apa.',
    'Ganti kata sandi awal segera setelah akun dibuat.',
    'Guru yang pindah atau pensiun: **nonaktifkan** akunnya, jangan dihapus.',
    'Pastikan orang tua benar-benar mengganti kata sandi bawaan. Kolom "Akun Ortu" di Data Siswa menunjukkan statusnya.',
    'Tutup semester tepat waktu — data yang sudah ditutup tidak bisa diubah siapa pun, termasuk karena kekeliruan.',
    'Jaga agar **nomor WhatsApp guru** di Kelola Pengguna selalu benar — itu yang dipakai pengingat jurnal otomatis.',
    'Token perangkat WhatsApp kepala sekolah adalah rahasia setara kata sandi. Siapa pun yang memegangnya bisa mengirim pesan atas nama kepala sekolah.',
]);

$d->h2('J.2 Cadangan data (backup)');
$d->p('Cadangkan database **setiap minggu**, dan **wajib** sebelum pergantian semester atau tahun ajaran.');
$d->kode("mysqldump -u pengguna -p --no-tablespaces --single-transaction sim_spenga > cadangan-2026-08-29.sql");
$d->p('Cadangkan juga folder `storage/app/public` yang berisi berkas unggahan: logo sekolah, ikon aplikasi, bukti pelanggaran & pembinaan BK, dan lampiran surat. Database saja belum lengkap — bila foldernya tidak ikut, catatan BK akan pulih tanpa foto buktinya dan kop surat kehilangan logonya.');
$d->catatan('Langkah lengkapnya ada di dokumen terpisah',
    'Cara mencadangkan lewat phpMyAdmin selangkah demi selangkah, cara memulihkan, cara mengosongkan data uji coba tanpa menghilangkan master data, dan perawatan berkala dibahas tuntas di **Panduan Database SIM-SPENGA**.');

$d->h2('J.3 Yang sudah dijaga aplikasi');
$d->tabel(['Perlindungan', 'Keterangan'], [
    ['Batas percobaan masuk', '5 percobaan per menit per akun & alamat'],
    ['Kata sandi', 'Disimpan teracak (hash), tidak bisa dibaca balik'],
    ['Batas antar peran', 'Guru tidak bisa membuka menu Admin, TU tidak bisa membuka BK, dan seterusnya'],
    ['Batas antar pemilik', 'Guru hanya bisa membuka kelas & mapel yang benar-benar diampunya'],
    ['Portal orang tua', 'Terpisah penuh dari area staf; hanya menampilkan data anaknya sendiri'],
    ['Perlindungan data', 'Menghapus master data yang masih dipakai ditolak, bukan dihapus diam-diam'],
    ['Lisensi', 'Aplikasi tidak berjalan tanpa nomor seri yang sah'],
    ['Token WhatsApp', 'Token perangkat kepala sekolah disimpan terenkripsi; membaca database langsung tidak memberikan tokennya'],
], [30, 70]);

// =====================================================================
$d->h1('Bagian K — Lampiran: Daftar Menu per Peran');

$d->h2('Admin');
$d->tabel(['Kelompok', 'Menu'], [
    ['Utama', 'Dashboard'],
    ['Kegiatan Mengajar', 'Absensi & Jurnal Mengajar, Absensi Kegiatan Sekolah, Absensi Ekstrakurikuler'],
    ['Penilaian', 'Daftar Nilai, Nilai Rapor Kelas, Nilai per Mata Pelajaran, Laporan Akhir Semester, Monitoring Input Nilai'],
    ['Monitoring', 'Rekap Absensi Kelas, Jurnal Mengajar Kelas, Jurnal Mengajar Guru, Kehadiran Mengajar Guru, Rekapitulasi Kepatuhan, Notifikasi WhatsApp Ortu'],
    ['Kesiswaan', 'Bimbingan Konseling, Kegiatan Sekolah, Ekstrakurikuler'],
    ['Administrasi Surat', 'Surat BK, Jenis Surat'],
    ['Data Master', 'Pemetaan Guru Mengajar, Pemetaan Guru BK, Jadwal Pelajaran, Data Siswa, Data Kelas, Mata Pelajaran, Tahun Ajaran'],
    ['Pengaturan', 'Pengaturan Sekolah, **Pengingat Guru (WA)**, Jenis Pelanggaran, Pengaturan Penilaian, Jam Pelajaran, Kelola Pengguna'],
], [26, 74]);

$d->h2('Kurikulum');
$d->tabel(['Kelompok', 'Menu'], [
    ['Kegiatan Mengajar', 'Absensi & Jurnal Mengajar'],
    ['Penilaian', 'Daftar Nilai, Nilai Rapor Kelas, Nilai per Mata Pelajaran, Laporan Akhir Semester, Monitoring Input Nilai'],
    ['Monitoring', 'Enam menu monitoring lengkap'],
    ['Kesiswaan', 'Bimbingan Konseling, Kegiatan Sekolah'],
    ['Administrasi Surat', 'Arsip Surat BK'],
    ['Data Master', 'Sama dengan Admin'],
    ['Pengaturan', 'Pengaturan Sekolah, Pengaturan Penilaian, Jam Pelajaran (lihat saja)'],
], [26, 74]);

$d->h2('Guru & Wali Kelas');
$d->tabel(['Kelompok', 'Menu'], [
    ['Kegiatan Mengajar', 'Absensi & Jurnal Mengajar, Absensi Kegiatan Sekolah, Absensi Ekstrakurikuler'],
    ['Penilaian', 'Daftar Nilai, Nilai Rapor Kelas, Nilai per Mata Pelajaran, Laporan Akhir Semester'],
    ['Monitoring', 'Rekap Absensi Kelas, Jurnal Mengajar Kelas, Jurnal Mengajar Guru, Kehadiran Mengajar Guru, Notifikasi WhatsApp Ortu'],
    ['Kesiswaan', 'Bimbingan Konseling'],
], [26, 74]);
$d->p('Empat menu Penilaian & Monitoring yang berkaitan dengan kelas hanya muncul bila akun itu **sedang** menjadi wali kelas pada periode yang dilihat.');

$d->h2('Guru BK');
$d->tabel(['Kelompok', 'Menu'], [
    ['Kegiatan Mengajar', 'Absensi Ekstrakurikuler'],
    ['Penilaian', 'Nilai Rapor Kelas, Nilai per Mata Pelajaran, Laporan Akhir Semester'],
    ['Monitoring', 'Rekap Absensi Kelas, Jurnal Mengajar Kelas, Notifikasi WhatsApp Ortu'],
    ['Kesiswaan', 'Bimbingan Konseling, Kegiatan Sekolah'],
    ['Administrasi Surat', 'Surat BK'],
    ['Pengaturan', 'Jenis Pelanggaran'],
], [26, 74]);

$d->h2('Kesiswaan, Kepala Sekolah, dan TU');
$d->tabel(['Peran', 'Menu'], [
    ['Kesiswaan', 'Absensi Ekstrakurikuler; Rekap Absensi Kelas; Notifikasi WhatsApp Ortu; Bimbingan Konseling; Kegiatan Sekolah; Ekstrakurikuler; Arsip Surat BK'],
    ['Kepala Sekolah', 'Seluruh menu Penilaian & Monitoring, Bimbingan Konseling, Kegiatan Sekolah, Arsip Surat BK — semuanya lihat & cetak saja'],
    ['Tata Usaha', 'Jenis Surat'],
], [22, 78]);

$d->h2('Ringkasan aturan periode');
$d->tabel(['Berlaku per SEMESTER', 'Berlaku untuk SELURUH periode'], [
    ['Mata Pelajaran, Jam Pelajaran', 'Akun Pengguna'],
    ['Jenis Pelanggaran, Jenis Surat', 'Pengaturan Sekolah & logo'],
    ['Ekstrakurikuler, pembina, anggota', 'Identitas Siswa (NIS, nama, orang tua)'],
    ['Kelas & daftar siswanya', ''],
    ['Wali Kelas, Guru Mengajar, Guru BK', ''],
    ['Jadwal Pelajaran', ''],
    ['Nilai, absensi, jurnal, BK, surat, kegiatan', ''],
], [55, 45]);

$d->p('');
$d->p('**Selesai.** Bila menemukan langkah yang tidak sesuai dengan tampilan aplikasi, sampaikan kepada Admin sekolah agar dokumen ini diperbarui.');

$keluar = __DIR__.'/Panduan-SIM-SPENGA.docx';
@mkdir(dirname($keluar), 0777, true);
$d->simpan($keluar);

echo "Dokumen dibuat: {$keluar}\n";
echo "Ukuran: ".number_format(filesize($keluar) / 1024, 1)." KB\n";
