<?php

/**
 * Penyusun berkas docs/Panduan-Database-Admin.docx.
 *
 * Jalankan dari akar proyek:  php docs/_pembuat-panduan-database.php
 *
 * Isinya khusus urusan DATABASE — mencadangkan, memulihkan, mengosongkan,
 * merawat, dan mengamankan. Panduan pemakaian menu sehari-hari ada di
 * berkas terpisah: docs/Panduan-SIM-SPENGA.docx.
 */

require __DIR__.'/_Docx.php';

$MYSQL = 'C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin';

$d = new Docx();

// =====================================================================
$d->sampulJudul('Panduan Database', 'SIM-SPENGA — SMP Negeri 3 Bumiayu', [
    'PEGANGAN ADMIN',
    'Mencadangkan, Memulihkan, Mengosongkan, dan Merawat Data',
    '',
    'Edisi Agustus 2026',
    '',
    'Dokumen ini untuk Admin. Jangan dibagikan ke pengguna umum —',
    'berisi perintah yang dapat menghapus seluruh data sekolah.',
]);

// =====================================================================
$d->h1('Daftar Isi');

$d->p('Bacalah **Bagian B (Mencadangkan)** lebih dulu, apa pun keperluan Anda. Semua tindakan lain di dokumen ini mengandaikan Anda sudah punya cadangan yang segar.');

$d->tabel(['Bagian', 'Isi', 'Seberapa sering dipakai'], [
    ['A', 'Mengenal database SIM-SPENGA', 'Sekali, di awal'],
    ['B', 'Mencadangkan database — WAJIB', 'Rutin, tiap minggu'],
    ['C', 'Memulihkan dari cadangan', 'Saat terjadi masalah'],
    ['D', 'Mengosongkan data untuk pemakaian sungguhan', 'Sekali seumur pemasangan'],
    ['E', 'Menghapus data lewat aplikasi (bukan database)', 'Sering'],
    ['F', 'Pergantian semester & tahun ajaran', 'Dua kali setahun'],
    ['G', 'Perawatan berkala', 'Tiap bulan'],
    ['H', 'Keamanan database', 'Sekali, saat pemasangan'],
    ['I', 'Bila terjadi masalah — gejala & tindakan', 'Saat diperlukan'],
    ['J', 'Lampiran: daftar perintah cepat', 'Rujukan'],
], [9, 61, 30]);

$d->catatan('Satu aturan yang tidak boleh dilanggar',
    'Sebelum menjalankan perintah apa pun yang mengubah atau menghapus data, **buat cadangan lebih dulu**. Perintah database tidak punya tombol "Undo". Cadangan adalah satu-satunya jalan pulang.',
    'FFD9D9');

// =====================================================================
$d->h1('Bagian A — Mengenal Database SIM-SPENGA');

$d->h2('A.1 Di mana data sekolah disimpan');

$d->p('Seluruh data SIM-SPENGA tersimpan di **dua tempat**, dan keduanya sama pentingnya:');

$d->tabel(['Tempat', 'Isinya', 'Bila hilang'], [
    ['Database MySQL bernama `sim_spenga`', 'Semua data teks & angka: siswa, nilai, absensi, jurnal, BK, surat, pengaturan, akun', 'Seluruh catatan sekolah lenyap'],
    ['Folder `storage/app/public`', 'Berkas yang diunggah: logo sekolah, ikon aplikasi, foto bukti pelanggaran & pembinaan BK, lampiran surat', 'Datanya masih ada, tapi gambar & lampirannya kosong'],
], [26, 50, 24]);

$d->catatan('Kesalahan yang paling sering terjadi',
    'Mencadangkan database saja, lupa foldernya. Saat dipulihkan, semua catatan BK masih ada tetapi **foto buktinya hilang semua** dan logo di kop surat menjadi kosong. Cadangkan keduanya bersamaan — lihat Bagian B.4.');

$d->h2('A.2 Tiga golongan tabel');

$d->p('Database ini berisi 53 tabel. Untuk keperluan pengelolaan, semuanya masuk salah satu dari tiga golongan berikut. Pembagian inilah yang dipakai perintah pengosongan data di Bagian D.');

$d->h3('Golongan 1 — Master data (13 tabel)');
$d->p('Daftar acuan yang disusun sekali lalu dipakai berulang. **Tidak pernah** ikut dikosongkan.');

$d->tabel(['Tabel', 'Isinya di aplikasi'], [
    ['`tahun_ajarans`', 'Daftar periode: 2026/2027 Ganjil, Genap, dan seterusnya'],
    ['`pengaturan_sekolahs`', 'Identitas sekolah, kepala sekolah, logo, format tanda tangan'],
    ['`pengaturan_penilaians`', 'Bobot & aturan penilaian per periode'],
    ['`kktp_tingkats`', 'Nilai KKTP per tingkat & mata pelajaran'],
    ['`users`', 'Akun guru, BK, kurikulum, kesiswaan, TU, kepala sekolah, admin'],
    ['`mata_pelajarans`', 'Daftar mata pelajaran'],
    ['`jam_pelajarans`', 'Jam ke-1 sampai ke-10 tiap hari beserta pukulnya'],
    ['`kelas`', 'Daftar kelas 7A, 7B, 8A, dan seterusnya'],
    ['`jenis_pelanggarans`', 'Katalog pelanggaran BK beserta poinnya'],
    ['`jenis_surats`', 'Katalog jenis surat'],
    ['`ekstrakurikulers`', 'Daftar nama kegiatan ekstrakurikuler'],
    ['`migrations`', 'Catatan struktur database — milik sistem, jangan disentuh'],
    ['`lisensi_aplikasis`', 'Bukti aktivasi nomor seri — milik sistem, jangan disentuh'],
], [30, 70]);

$d->h3('Golongan 2 — Data sekolah (34 tabel)');
$d->p('Hasil pemakaian sehari-hari. Inilah yang dikosongkan bila aplikasi hendak dipakai sungguhan setelah masa uji coba.');

$d->tabel(['Kelompok', 'Tabelnya'], [
    ['Siswa & penempatannya', '`siswas`, `anggota_kelas`, `riwayat_kelas_siswas`, `orang_tuas`, `orang_tua_siswa`'],
    ['Penugasan guru', '`penugasan_wali_kelas`, `guru_mengajar_kelas`, `guru_bk_kelas`'],
    ['Jadwal & mengajar', '`jadwal_pelajarans`, `jurnal_mengajars`, `jurnal_mengajar_slots`'],
    ['Kehadiran', '`absensi_siswas`, `absensi_kegiatans`, `absensi_ekskuls`, `absensi_ekskul_pesertas`'],
    ['Penilaian', '`nilai_siswas`, `penilaian_kelas_mapels`, `analisis_sumatifs`'],
    ['Bimbingan Konseling', '`kasus_siswas`, `pembinaan_siswas`, `evaluasi_pembinaans`, `pemanggilan_orangtuas`, `pengurangan_poin_siswas`'],
    ['Surat', '`surats`, `surat_siswa`, `surat_activities`, `surat_attachments`, `disposisi_surats`'],
    ['Kegiatan & ekstrakurikuler', '`kegiatan_sekolahs`, `kegiatan_kelas`, `ekstrakurikuler_siswas`, `ekstrakurikuler_pembinas`'],
    ['Notifikasi', '`notifikasi_was`, `notifikasi_alfa_terkirims`'],
], [28, 72]);

$d->h3('Golongan 3 — Data sementara sistem (6 tabel)');
$d->p('`sessions`, `cache`, `cache_locks`, `jobs`, `failed_jobs`, `password_reset_tokens`. Bukan data sekolah. Mengosongkannya aman kapan saja — akibatnya hanya semua orang perlu login lagi.');

$d->h2('A.3 Aturan periode yang memengaruhi database');

$d->p('SIM-SPENGA menyimpan **hampir semua data per semester**, bukan per tahun. Mata pelajaran, jam pelajaran, jenis pelanggaran, jenis surat, ekstrakurikuler, kelas beserta daftar siswanya, wali kelas, pembina ekskul, penugasan guru, jadwal — semuanya punya kolom `tahun_ajaran_id`.');

$d->p('Hanya tiga hal yang berlaku lintas periode: **akun pengguna** (`users`), **pengaturan sekolah**, dan **identitas siswa** (NIS, nama, nama orang tua).');

$d->catatan('Akibatnya saat membaca database langsung',
    'Satu kelas "7A" bisa muncul beberapa baris di tabel `kelas` — satu untuk tiap semester. Ini **bukan data ganda**. Bila Anda menghapusnya "karena kelihatan dobel", data semester lain akan ikut rusak. Selalu perhatikan kolom `tahun_ajaran_id`.',
    'FFD9D9');

// =====================================================================
$d->h1('Bagian B — Mencadangkan Database');

$d->h2('B.1 Kapan wajib mencadangkan');

$d->tabel(['Waktu', 'Alasan'], [
    ['Setiap minggu, terjadwal', 'Kerusakan disk & salah hapus tidak memberi aba-aba'],
    ['Sebelum mengganti semester atau tahun ajaran', 'Proses penyalinan menyentuh banyak tabel sekaligus'],
    ['Sebelum menjalankan `php artisan migrate`', 'Perubahan struktur tidak selalu bisa dibalik'],
    ['Sebelum mengosongkan data (Bagian D)', 'Penghapusannya permanen'],
    ['Sebelum mengimpor Excel dalam jumlah besar', 'Kesalahan format bisa membuat ratusan baris salah'],
    ['Di akhir tiap semester, disimpan permanen', 'Arsip resmi nilai & absensi semester itu'],
], [42, 58]);

$d->h2('B.2 Cara termudah — lewat phpMyAdmin');

$d->p('Cocok untuk Admin yang tidak terbiasa dengan baris perintah.');

$d->langkah([
    'Buka Laragon, klik tombol **Database** (atau buka `http://localhost/phpmyadmin` di peramban).',
    'Di daftar kiri, klik database **`sim_spenga`**. Pastikan yang terpilih memang database ini, bukan yang lain.',
    'Klik tab **Export** di bagian atas.',
    'Pilih **Custom** pada bagian Export method — jangan Quick.',
    'Pada Format pilih **SQL**. Pada Output pilih **Save output to a file**.',
    'Pada bagian Compression pilih **zipped** supaya berkasnya kecil.',
    'Gulir ke bawah, klik **Export**. Berkas akan terunduh ke folder Downloads.',
    'Ganti nama berkasnya menjadi berpola tanggal, contoh: `sim_spenga-2026-08-29.sql.zip`.',
    'Pindahkan ke tempat penyimpanan cadangan (lihat B.4).',
]);

$d->h2('B.3 Cara cepat — lewat baris perintah');

$d->p('Sekali diketik, hasilnya sama dan jauh lebih cepat. Buka Laragon, klik kanan ikonnya, pilih **Terminal**, lalu:');

$d->kode('cd C:\laragon\www\sim-spenga

"'.$MYSQL.'\mysqldump" -u root --no-tablespaces --single-transaction sim_spenga > cadangan-sim_spenga-2026-08-29.sql');

$d->p('Bila pengguna database Anda bukan `root` atau memakai kata sandi, tambahkan `-p` di belakang nama pengguna — nanti kata sandinya ditanyakan:');

$d->kode('"'.$MYSQL.'\mysqldump" -u pengguna_sekolah -p --no-tablespaces --single-transaction sim_spenga > cadangan.sql');

$d->tabel(['Bagian perintah', 'Gunanya'], [
    ['`--single-transaction`', 'Mengambil potret data pada satu titik waktu, jadi aman dijalankan saat guru sedang memakai aplikasi'],
    ['`--no-tablespaces`', 'Menghindari galat izin yang lazim muncul di MySQL 8'],
    ['`> namaberkas.sql`', 'Menyimpan hasilnya ke berkas'],
], [30, 70]);

$d->h2('B.4 Mencadangkan berkas unggahan juga');

$d->p('Database saja belum lengkap. Salin juga folder berikut:');

$d->kode('C:\laragon\www\sim-spenga\storage\app\public');

$d->p('Isinya empat folder: `bk` (foto bukti pelanggaran & pembinaan), `ikon` (ikon aplikasi), `pengaturan-sekolah` (logo kop surat), dan `surat-lampiran` (lampiran surat). Cara termudah: klik kanan folder `public` tersebut, pilih **Send to → Compressed (zipped) folder**, lalu simpan bersama berkas `.sql`-nya.');

$d->h2('B.5 Di mana cadangan disimpan');

$d->p('Cadangan yang disimpan di komputer yang sama dengan aplikasinya **bukan cadangan** — bila komputer itu rusak atau terkena ransomware, keduanya hilang bersamaan. Simpan minimal di dua tempat berbeda:');

$d->poin([
    'Satu salinan di flashdisk atau hard disk eksternal yang disimpan terpisah, di lemari sekolah.',
    'Satu salinan di penyimpanan awan sekolah (Google Drive / OneDrive akun sekolah, bukan akun pribadi).',
    'Simpan cadangan akhir semester secara permanen — jangan ditimpa. Itu arsip resmi.',
]);

$d->catatan('Cadangan berisi data pribadi siswa',
    'Berkas cadangan memuat NIS, nama, nama orang tua, nomor WhatsApp, nilai, dan catatan BK. Perlakukan seperti dokumen rahasia sekolah: jangan diunggah ke penyimpanan pribadi, jangan dikirim lewat aplikasi obrolan, dan jangan ditinggal di flashdisk yang berpindah tangan.');

$d->h2('B.6 Memastikan cadangan benar-benar berisi');

$d->p('Cadangan yang gagal biasanya berukuran nol atau beberapa baris saja, dan baru ketahuan saat dibutuhkan. Periksa dua hal setiap kali mencadangkan:');

$d->poin([
    '**Ukurannya wajar** — untuk sekolah dengan data satu tahun, berkas `.sql` biasanya ratusan KB sampai beberapa MB. Kalau hanya beberapa KB, cadangannya gagal.',
    '**Ada baris `INSERT INTO`** — buka berkasnya dengan Notepad, tekan Ctrl+F, cari `INSERT INTO `siswas``. Kalau tidak ketemu, datanya tidak ikut tersalin.',
]);

// =====================================================================
$d->h1('Bagian C — Memulihkan dari Cadangan');

$d->catatan('Memulihkan akan MENIMPA data yang ada sekarang',
    'Seluruh isi database saat ini digantikan oleh isi cadangan. Data yang masuk setelah cadangan itu dibuat akan hilang. Bila ragu, cadangkan dulu keadaan sekarang ke berkas lain sebelum memulihkan.',
    'FFD9D9');

$d->h2('C.1 Lewat phpMyAdmin');

$d->langkah([
    'Buka `http://localhost/phpmyadmin`.',
    'Klik database **`sim_spenga`** di daftar kiri.',
    'Klik tab **Operations**, gulir ke **Remove database (DROP)**, klik, lalu setujui. Ini mengosongkan database secara menyeluruh.',
    'Buat lagi database kosong bernama sama: klik **New** di kiri atas, ketik `sim_spenga`, pilih collation **utf8mb4_unicode_ci**, klik **Create**.',
    'Klik database `sim_spenga` yang baru, lalu tab **Import**.',
    'Klik **Choose File**, pilih berkas cadangan `.sql` (atau `.zip`-nya langsung — phpMyAdmin bisa membaca zip).',
    'Gulir ke bawah, klik **Import**. Tunggu sampai muncul keterangan berhasil.',
    'Kembalikan juga folder `storage/app/public` dari cadangannya.',
]);

$d->h2('C.2 Lewat baris perintah');

$d->kode('cd C:\laragon\www\sim-spenga

"'.$MYSQL.'\mysql" -u root -e "DROP DATABASE IF EXISTS sim_spenga; CREATE DATABASE sim_spenga CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

"'.$MYSQL.'\mysql" -u root sim_spenga < cadangan-sim_spenga-2026-08-29.sql');

$d->h2('C.3 Setelah pemulihan — tiga langkah penutup');

$d->langkah([
    'Bersihkan cache aplikasi: `php artisan optimize:clear`',
    'Buka aplikasi di peramban dan login. Bila diminta memasukkan nomor seri, lihat Bagian I.',
    'Periksa satu halaman berisi data — misalnya Data Siswa dan Daftar Nilai satu kelas — untuk memastikan isinya benar.',
]);

// =====================================================================
$d->h1('Bagian D — Mengosongkan Data untuk Pemakaian Sungguhan');

$d->h2('D.1 Kapan bagian ini dipakai');

$d->p('Saat aplikasi selesai diuji coba dan hendak dipakai sungguhan. Seluruh data percobaan dibuang, sementara daftar acuan yang sudah susah payah disusun — mata pelajaran, jam pelajaran, 156 jenis pelanggaran, jenis surat, kelas, ekstrakurikuler, akun guru, pengaturan sekolah — tetap utuh.');

$d->p('Bagian ini biasanya dijalankan **sekali seumur pemasangan**. Untuk pergantian semester, jangan pakai ini — lihat Bagian F.');

$d->h2('D.2 Apa yang dihapus dan apa yang tinggal');

$d->tabel(['Tetap ada sesudahnya', 'Hilang, harus diisi ulang'], [
    ['Akun guru & admin beserta kata sandinya', 'Data siswa'],
    ['Tahun ajaran & pengaturan sekolah', 'Penempatan siswa ke kelas'],
    ['Mata pelajaran', 'Penugasan wali kelas'],
    ['Jam pelajaran', 'Penugasan guru mengajar & guru BK'],
    ['Daftar kelas (7A, 7B, …)', 'Jadwal pelajaran'],
    ['Jenis pelanggaran & jenis surat', 'Pembina & anggota ekstrakurikuler'],
    ['Nama kegiatan ekstrakurikuler', 'Akun portal orang tua'],
    ['Pengaturan penilaian & KKTP', 'Semua absensi, nilai, jurnal, BK, surat, kegiatan'],
    ['Aktivasi nomor seri', ''],
], [50, 50]);

$d->h2('D.3 Langkah menjalankannya');

$d->langkah([
    'Buka Laragon → klik kanan ikonnya → **Terminal**.',
    'Masuk ke folder aplikasi: `cd C:\laragon\www\sim-spenga`',
    'Lihat dulu rencananya, **belum menghapus apa pun**: `php artisan data:kosongkan --lihat`',
    'Periksa daftar hijau paling bawah bertajuk DIPERTAHANKAN. Pastikan jumlah mata pelajaran, jam pelajaran, jenis pelanggaran, kelas, dan akun sudah sesuai.',
    'Bila sudah yakin, jalankan: `php artisan data:kosongkan`',
    'Perintah membuat cadangan otomatis lebih dulu ke folder `storage\app\cadangan`. Perhatikan nama berkasnya — itu jalan pulang Anda.',
    'Ketik **HAPUS** (huruf besar semua) lalu tekan Enter. Mengetik apa pun selain itu akan membatalkan.',
    'Tunggu sampai muncul "Selesai. Master data tetap utuh".',
    'Buka aplikasi di peramban dan login lagi — sesi login ikut dikosongkan.',
]);

$d->catatan('Perintah ini menolak berjalan tanpa cadangan',
    'Bila `mysqldump` tidak ditemukan di server, perintah **berhenti sendiri** dan tidak menghapus apa pun. Buat cadangan manual lewat phpMyAdmin (Bagian B.2), lalu ulangi dengan tambahan `--tanpa-cadangan` di belakang perintahnya.');

$d->h2('D.4 Urutan pengisian ulang');

$d->p('Kelas 7A sampai 9B tetap ada, tetapi **kosong dan tanpa wali kelas**. Isi kembali dengan urutan ini supaya tidak ada langkah yang terhambat menunggu data lain:');

$d->tabel(['Urutan', 'Menu', 'Catatan'], [
    ['1', 'Data Siswa', 'Pakai **Import Excel** — sekalian menempatkan siswa ke kelasnya'],
    ['2', 'Kelas → Wali Kelas', 'Penugasan berlaku per semester'],
    ['3', 'Kurikulum → Guru Mengajar', 'Boleh lewat Import Excel juga'],
    ['4', 'Kurikulum → Guru BK', 'Menentukan kelas binaan tiap guru BK'],
    ['5', 'Jadwal Pelajaran', 'Jam pelajaran & mapel sudah ada, tinggal disusun'],
    ['6', 'Ekstrakurikuler → Pembina & Anggota', 'Nama kegiatannya tetap ada'],
    ['7', 'Akun Portal Orang Tua', 'Dibuat dari menu Data Siswa'],
], [12, 33, 55]);

$d->h2('D.5 Bila ternyata salah');

$d->p('Pulihkan dari cadangan otomatis yang dibuat perintah tadi:');

$d->kode('cd C:\laragon\www\sim-spenga

"'.$MYSQL.'\mysql" -u root sim_spenga < "storage\app\cadangan\sebelum-kosongkan-20260829-093000.sql"');

$d->p('Ganti bagian tanggalnya sesuai nama berkas yang tampil di layar saat perintah dijalankan.');

$d->h2('D.6 Catatan tambahan');

$d->poin([
    '**Nomor ID kembali mulai dari 1**, jadi database benar-benar seperti pemasangan baru.',
    '**Aktivasi nomor seri tidak ikut terhapus** — tidak perlu diaktifkan ulang selama alamat akses aplikasinya tidak berubah.',
    'Foto bukti BK dan lampiran surat di folder `storage/app/public` **tidak ikut terhapus**. Bila ingin benar-benar bersih, hapus isi folder `bk` dan `surat-lampiran` secara manual. Jangan hapus folder `ikon` dan `pengaturan-sekolah` — di situ logo sekolahnya.',
]);

// =====================================================================
$d->h1('Bagian E — Menghapus Data Lewat Aplikasi');

$d->p('Untuk penghapusan sehari-hari, **jangan** menyentuh database. Pakai menu di aplikasi — di situ ada pengaman yang tidak ada bila Anda menghapus langsung lewat phpMyAdmin.');

$d->h2('E.1 Arti pesan "tidak dapat dihapus karena masih dipakai"');

$d->p('Aplikasi menolak menghapus data yang masih menjadi rujukan data lain, dan pesannya **menyebut angkanya**. Contoh:');

$d->kode('Mata pelajaran ini tidak dapat dihapus
— masih dipakai 12 jadwal pelajaran dan 340 nilai siswa.');

$d->p('Ini bukan kerusakan, melainkan pengaman. Tanpa itu, menghapus satu mata pelajaran akan ikut menghapus seluruh nilai siswa pada mata pelajaran tersebut, diam-diam, tanpa pemberitahuan.');

$d->h2('E.2 Nonaktifkan, bukan hapus');

$d->p('Untuk hampir semua keadaan, jawabannya adalah **menonaktifkan**, bukan menghapus. Data lama tetap utuh untuk laporan dan rapor, tetapi tidak lagi muncul sebagai pilihan saat mengisi data baru.');

$d->tabel(['Keadaan', 'Yang benar dilakukan'], [
    ['Guru pensiun atau pindah tugas', 'Kelola Pengguna → Edit → hilangkan centang **Aktif** → Simpan. Jurnal & nilai yang pernah diisinya tetap ada.'],
    ['Siswa lulus, pindah, atau keluar', 'Data Siswa → Edit → hilangkan centang **Aktif** → Simpan. Rapor & catatan BK-nya tetap tersimpan.'],
    ['Ekstrakurikuler berhenti berjalan', 'Ekstrakurikuler → Edit → hilangkan centang **Aktif** → Simpan.'],
    ['Jenis pelanggaran tidak dipakai lagi', 'Jenis Pelanggaran → Edit → hilangkan centang **Aktif** → Simpan.'],
    ['Jam pelajaran berubah susunannya', 'Jam Pelajaran → Edit jam yang ada. Perubahan otomatis berlaku di seluruh jadwal hari itu.'],
    ['Data memang **salah dibuat** dan belum dipakai', 'Tombol tong sampah — akan langsung terhapus.'],
], [34, 66]);

$d->h2('E.3 Kasus khusus: menghapus ekstrakurikuler');

$d->p('Kegiatan ekstrakurikuler yang sudah punya anggota atau pernah sekali saja diabsen tidak bisa dihapus dengan tombol tong sampah biasa — sengaja, supaya riwayat kehadiran tidak lenyap oleh satu klik.');

$d->p('Bila kegiatannya memang **salah dibuat** dan harus benar-benar hilang:');

$d->langkah([
    'Buka menu **Ekstrakurikuler**.',
    'Klik ikon **pensil** pada kegiatan yang dimaksud untuk membuka panel Edit.',
    'Gulir ke bawah panel, ke bagian bergaris merah bertajuk **Hapus Kegiatan**.',
    'Klik **Hapus permanen beserta datanya**. Tombolnya menyebutkan berapa anggota dan berapa sesi absensi yang ikut terhapus.',
    'Baca kotak konfirmasinya, lalu setujui bila sudah yakin.',
]);

$d->catatan('Kegiatan bernama sama di semester lain tidak ikut terhapus',
    'Karena ekstrakurikuler tercatat per semester, "Pramuka" Semester Ganjil dan "Pramuka" Semester Genap adalah dua baris berbeda. Menghapus salah satunya tidak menyentuh yang lain.');

$d->h2('E.4 Bila terpaksa menghapus lewat phpMyAdmin');

$d->p('Sedapat mungkin jangan. Bila memang tidak ada jalan lain:');

$d->poin([
    'Cadangkan dulu. Tanpa pengecualian.',
    'Perhatikan kolom `tahun_ajaran_id` — pastikan Anda menghapus baris milik semester yang benar.',
    'Jangan mematikan pemeriksaan foreign key untuk "menerobos" penolakan. Penolakan itu ada alasannya, dan menerobosnya meninggalkan data rujukan yang menggantung — halaman aplikasi akan mulai menampilkan galat yang sulit dilacak.',
    'Setelah selesai, jalankan `php artisan optimize:clear` dan periksa halaman yang berkaitan.',
]);

// =====================================================================
$d->h1('Bagian F — Pergantian Semester & Tahun Ajaran');

$d->h2('F.1 Yang terjadi pada database');

$d->p('Setiap periode baru — baik ganti semester maupun ganti tahun ajaran — membuat **kumpulan baris baru** di hampir semua tabel. Data periode lama tidak dipindah, tidak ditimpa, dan tidak dihapus. Ia hanya berhenti bertambah.');

$d->p('Karena itu, database akan tumbuh setiap semester. Ini normal dan justru yang diinginkan: nilai Semester Ganjil tetap bisa dibuka bertahun-tahun kemudian, persis seperti saat ditutup.');

$d->h2('F.2 Langkah pergantian yang benar');

$d->langkah([
    'Cadangkan database dan folder unggahan (Bagian B).',
    'Buka menu **Tahun Ajaran**, buat periode baru bila belum ada.',
    'Pakai fitur **Duplikasi Data Periode** untuk menyalin master data dari periode lama: mata pelajaran, jam pelajaran, jenis pelanggaran, jenis surat, ekstrakurikuler, kelas, dan penugasan guru. Lihat pratinjaunya lebih dulu.',
    'Aktifkan periode baru.',
    'Sesuaikan yang memang berubah: susunan kelas, wali kelas, guru mengajar, jadwal.',
    'Setelah semua nilai periode lama selesai diisi dan dicetak, **kunci** periode lama lewat menu Tahun Ajaran.',
]);

$d->catatan('Mengunci bukan menghapus',
    'Periode yang dikunci masih bisa dibuka dan dicetak oleh semua guru lewat kotak pemilih periode di kanan atas — hanya saja tidak bisa diubah lagi. Guru yang dulu menjadi wali kelas tetap melihat menu dan data perwaliannya saat memilih periode itu.');

$d->h2('F.3 Jangan pakai perintah pengosongan untuk ganti tahun');

$d->p('`php artisan data:kosongkan` menghapus **seluruh** riwayat, termasuk nilai dan absensi tahun-tahun sebelumnya. Untuk pergantian tahun ajaran, yang benar adalah membuat periode baru — bukan mengosongkan yang lama.');

// =====================================================================
$d->h1('Bagian G — Perawatan Berkala');

$d->h2('G.1 Sekali sebulan');

$d->tabel(['Yang diperiksa', 'Caranya', 'Batas wajar'], [
    ['Ukuran database', 'phpMyAdmin → klik `sim_spenga` → lihat kolom Size di bawah daftar tabel', 'Di bawah 500 MB untuk beberapa tahun pemakaian'],
    ['Ukuran folder unggahan', 'Klik kanan `storage\app\public` → Properties', 'Foto bukti BK yang paling cepat menumpuk'],
    ['Berkas log', 'Buka folder `storage\logs`', 'Hapus berkas `laravel-*.log` yang lebih lama dari 3 bulan'],
    ['Cadangan terakhir', 'Periksa tanggal berkas cadangan Anda', 'Tidak lebih lama dari 7 hari'],
], [24, 46, 30]);

$d->h2('G.2 Membersihkan data sementara');

$d->p('Bila aplikasi terasa lambat atau sesudah memulihkan cadangan:');

$d->kode('cd C:\laragon\www\sim-spenga

php artisan optimize:clear');

$d->p('Perintah ini membersihkan cache konfigurasi, rute, tampilan, dan data sementara. Aman dijalankan kapan saja. Sesudahnya, permintaan pertama akan terasa sedikit lebih lambat — itu wajar.');

$d->h2('G.3 Tabel yang tumbuh paling cepat');

$d->p('Bila suatu saat database terasa besar, empat tabel inilah penyumbang terbesarnya:');

$d->tabel(['Tabel', 'Sebabnya'], [
    ['`absensi_siswas`', 'Satu baris per siswa per jam pelajaran per hari'],
    ['`nilai_siswas`', 'Satu baris per siswa per penilaian per mata pelajaran'],
    ['`jurnal_mengajar_slots`', 'Satu baris per jam mengajar tiap guru'],
    ['`sessions`', 'Bertambah tiap login; aman dikosongkan kapan saja'],
], [30, 70]);

$d->p('Ketiga tabel pertama adalah catatan resmi sekolah — **jangan dihapus** untuk menghemat tempat. Bila memang perlu, pindahkan periode lama ke arsip cadangan permanen dan konsultasikan dengan penyedia aplikasi lebih dulu.');

// =====================================================================
$d->h1('Bagian H — Keamanan Database');

$d->h2('H.1 Jangan memakai root tanpa kata sandi');

$d->p('Bawaan Laragon adalah pengguna `root` tanpa kata sandi. Itu wajar untuk komputer pengembangan, **tidak boleh** untuk server sekolah yang dipakai sungguhan. Buat pengguna khusus yang hanya berhak atas database ini:');

$d->kode('CREATE USER \'spenga\'@\'localhost\' IDENTIFIED BY \'kata-sandi-yang-panjang-dan-acak\';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES
  ON sim_spenga.* TO \'spenga\'@\'localhost\';
FLUSH PRIVILEGES;');

$d->p('Lalu ubah `DB_USERNAME` dan `DB_PASSWORD` di berkas `.env`, dan jalankan `php artisan config:clear`.');

$d->h2('H.2 Berkas .env adalah kunci rumah');

$d->tabel(['Isi .env', 'Kalau bocor'], [
    ['`DB_PASSWORD`', 'Seluruh data sekolah bisa diunduh orang lain'],
    ['`APP_KEY`', 'Cookie login bisa dipalsukan — orang bisa masuk sebagai admin'],
    ['`WA_API_TOKEN`', 'Nomor WhatsApp sekolah bisa dipakai mengirim pesan atas nama sekolah'],
], [26, 74]);

$d->p('Jangan pernah menyalin `.env` ke folder `public`, mengirimnya lewat aplikasi obrolan, atau menyertakannya dalam berkas yang dibagikan.');

$d->catatan('APP_KEY jangan diganti setelah aplikasi dipakai',
    'Mengganti `APP_KEY` membuat **semua orang ter-logout** sekaligus **membatalkan aktivasi nomor seri** — aplikasi akan meminta nomor seri lagi. Kunci ini dibuat sekali saat pemasangan, lalu dibiarkan.',
    'FFD9D9');

$d->h2('H.3 Pengaturan wajib di server sekolah');

$d->tabel(['Setelan di .env', 'Nilai', 'Alasan'], [
    ['`APP_DEBUG`', '`false`', 'Bila `true`, setiap galat menampilkan isi `.env` termasuk kata sandi database kepada siapa pun yang memicunya'],
    ['`APP_ENV`', '`production`', 'Mematikan sejumlah kelonggaran yang hanya pantas untuk pengembangan'],
    ['`SESSION_SECURE_COOKIE`', '`true`', 'Wajib bila aplikasi diakses lewat https — mencegah cookie login tercuri di jaringan wifi sekolah'],
    ['`SESSION_ENCRYPT`', '`true`', 'Isi sesi tidak terbaca walau berkasnya terambil'],
    ['`LOG_LEVEL`', '`warning`', 'Log tidak membengkak oleh catatan yang tidak perlu'],
], [26, 14, 60]);

$d->p('Contoh lengkapnya tersedia di berkas `.env.production.example` pada folder aplikasi.');

$d->h2('H.4 phpMyAdmin jangan terbuka dari luar');

$d->p('Bila server sekolah bisa diakses dari internet, pastikan `phpmyadmin` hanya bisa dibuka dari jaringan sekolah. phpMyAdmin yang terbuka adalah pintu masuk paling sering dipakai untuk mencuri isi database.');

// =====================================================================
$d->h1('Bagian I — Bila Terjadi Masalah');

$d->tabel(['Gejala', 'Penyebab yang paling mungkin', 'Tindakan'], [
    [
        'Aplikasi meminta nomor seri padahal dulu sudah diaktifkan',
        'Alamat akses berubah (mis. dari `127.0.0.1` menjadi `localhost`), atau `APP_KEY` berganti',
        'Buka aplikasi lewat alamat yang sama seperti sebelumnya. Bila memang alamatnya pindah, masukkan nomor seri sekali lagi.',
    ],
    [
        'Halaman putih atau galat 500 di semua menu',
        'Cache lama tidak cocok dengan kode yang baru',
        '`php artisan optimize:clear`',
    ],
    [
        'Galat "Too many connections"',
        'Terlalu banyak koneksi database menumpuk',
        'Muat ulang halaman. Bila berulang, jalankan ulang MySQL lewat Laragon (Stop lalu Start).',
    ],
    [
        'Admin lupa kata sandi',
        '—',
        'Lihat I.1 di bawah',
    ],
    [
        'Logo hilang dari kop surat, foto bukti BK kosong',
        'Folder `storage/app/public` tidak ikut dipulihkan, atau tautan `public/storage` putus',
        'Kembalikan foldernya dari cadangan, lalu jalankan `php artisan storage:link`',
    ],
    [
        'Data satu semester terlihat kosong padahal dulu ada',
        'Kotak pemilih periode di kanan atas sedang menunjuk periode lain',
        'Ganti pilihan periodenya. Ini bukan kehilangan data.',
    ],
    [
        'Setelah `php artisan migrate` aplikasi galat',
        'Struktur berubah tetapi ada data lama yang tidak cocok',
        'Pulihkan dari cadangan sebelum migrasi, lalu hubungi penyedia aplikasi.',
    ],
], [24, 34, 42]);

$d->h2('I.1 Mengganti kata sandi yang terlupa');

$d->p('Bila masih ada admin lain yang bisa masuk, ganti lewat menu **Kelola Pengguna**. Bila tidak ada satu pun yang bisa masuk, lewat terminal:');

$d->kode('cd C:\laragon\www\sim-spenga

php artisan tinker');

$d->p('Setelah muncul tanda `>>>`, ketik dua baris ini, ganti alamat surel dan kata sandinya:');

$d->kode('$u = App\Models\User::where(\'email\', \'admin@spenga.sch.id\')->first();
$u->password = \'SandiBaruYangKuat123\'; $u->save();');

$d->p('Ketik `exit` untuk keluar. Kata sandinya otomatis diacak dan disimpan dalam bentuk terenkripsi — tidak tersimpan apa adanya di database.');

$d->catatan('Segera ganti lagi setelah bisa masuk',
    'Kata sandi yang diketik di terminal tercatat di riwayat perintah. Setelah berhasil login, ganti sekali lagi lewat menu Kelola Pengguna.');

// =====================================================================
$d->h1('Bagian J — Lampiran: Daftar Perintah Cepat');

$d->p('Semua perintah dijalankan dari folder aplikasi. Buka Laragon → klik kanan ikonnya → **Terminal**, lalu:');

$d->kode('cd C:\laragon\www\sim-spenga');

$d->h2('Mencadangkan & memulihkan');

$d->kode('REM Mencadangkan
"'.$MYSQL.'\mysqldump" -u root --no-tablespaces --single-transaction sim_spenga > cadangan.sql

REM Memulihkan (menimpa data yang ada!)
"'.$MYSQL.'\mysql" -u root sim_spenga < cadangan.sql');

$d->h2('Mengosongkan data');

$d->kode('REM Melihat rencananya saja, tidak menghapus
php artisan data:kosongkan --lihat

REM Menjalankan penghapusan (akan minta ketik HAPUS)
php artisan data:kosongkan');

$d->h2('Perawatan');

$d->kode('REM Membersihkan seluruh cache
php artisan optimize:clear

REM Memperbaiki tautan folder unggahan
php artisan storage:link

REM Menerapkan perubahan struktur database
php artisan migrate --force');

$d->h2('Ringkasan tanggung jawab Admin');

$d->tabel(['Kapan', 'Yang dikerjakan'], [
    ['Tiap minggu', 'Cadangkan database + folder `storage/app/public`; simpan di dua tempat berbeda'],
    ['Tiap bulan', 'Periksa ukuran database, bersihkan log lama, pastikan cadangan terakhir masih segar'],
    ['Tiap akhir semester', 'Cadangan permanen sebagai arsip; kunci periode lama'],
    ['Tiap ganti tahun ajaran', 'Cadangkan, buat periode baru, duplikasi master data, sesuaikan kelas & penugasan'],
    ['Sebelum tindakan besar apa pun', 'Cadangkan lebih dulu — tanpa pengecualian'],
], [26, 74]);

$d->p('');
$d->p('**Selesai.** Bila menemukan langkah yang tidak sesuai dengan tampilan aplikasi, catat dan sampaikan kepada penyedia aplikasi agar dokumen ini diperbarui.');

// =====================================================================
$keluar = __DIR__.'/Panduan-Database-Admin.docx';
$d->simpan($keluar);

echo "Dokumen dibuat: {$keluar}\n";
echo "Ukuran: ".number_format(filesize($keluar) / 1024, 1)." KB\n";
