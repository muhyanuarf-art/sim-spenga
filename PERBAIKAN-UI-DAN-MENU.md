# Perbaikan Tampilan, Menu, Sidebar & Dashboard — SIM-SPENGA

Dokumen ini merangkum apa yang diubah, mengapa, dan apa yang perlu dijalankan setelah update.

---

## 1. Yang harus dijalankan setelah menimpa file

```bash
php artisan optimize:clear      # bersihkan cache config/route/view lama
npm install                     # hanya jika node_modules bermasalah
npm run build                   # WAJIB kalau ada perubahan CSS/JS lagi
```

Catatan: hasil build CSS terbaru sudah disertakan di `public/build/assets/`, jadi
aplikasi langsung tampil benar walau `npm run build` belum sempat dijalankan.
Tetap jalankan `npm run build` di server saat deploy berikutnya.

---

## 2. Masalah yang diperbaiki

### 2.1 Menu & sidebar berantakan (akar masalahnya)
Dulu seluruh struktur menu ditulis manual di `layouts/app.blade.php` sebagai
tumpukan `@if role`. Akibatnya:

* satu fitur ditulis berulang untuk role berbeda (menu Surat ditulis **3×**,
  menu Pelanggaran **2×**) sehingga gampang tidak sinkron;
* muncul akal-akalan penamaan seperti `Surat — Dashboard`, `Surat — Buat Baru`,
  `+ Buat Surat`, `Data Pelanggaran (Master)`;
* tidak ada pengelompokan, semua menu berderet rata tanpa judul bagian;
* tidak ada tempat menyimpan deskripsi halaman, sehingga tidak ada breadcrumb.

**Sekarang:** seluruh menu dideklarasikan **satu kali** di
`app/Support/Navigasi.php`. Sidebar, breadcrumb, sub-judul halaman, dan
pengecekan hak akses semuanya membaca dari sana. Menambah/mengubah menu cukup
di satu file itu.

### 2.2 Struktur menu baru (dikelompokkan per bagian)

| Bagian | Isi |
|---|---|
| **Utama** | Dashboard |
| **Kegiatan Mengajar** | Absensi & Jurnal Mengajar · Absensi Ekstrakurikuler |
| **Monitoring** | Rekap Absensi Kelas · Jurnal Mengajar Kelas · Jurnal Mengajar Guru · Kehadiran Mengajar Guru · Rekapitulasi Kepatuhan · Notifikasi WhatsApp Ortu |
| **Kesiswaan** | Bimbingan Konseling (7 sub menu) · Ekstrakurikuler |
| **Administrasi Surat** | Surat BK (5 sub menu) · Arsip Surat BK (baca saja) · Jenis Surat |
| **Data Master** | Pemetaan Guru Mengajar · Pemetaan Guru BK · Jadwal Pelajaran · Data Siswa · Akun Orang Tua · Data Kelas · Mata Pelajaran · Tahun Ajaran |
| **Pengaturan** | Pengaturan Sekolah · Jam Pelajaran · Kelola Pengguna |

Menu Data Master sengaja dibuat **rata (tanpa accordion)** supaya setiap
halaman cukup satu klik — sebelumnya harus buka grup dulu baru pilih.

### 2.3 Penamaan menu dirapikan & diseragamkan

| Sebelum | Sesudah |
|---|---|
| Pantau Pelanggaran | Ringkasan Pelanggaran |
| Kasus/Pelanggaran | Kasus & Pelanggaran |
| Data Pelanggaran (Master) | Master Jenis Pelanggaran |
| Monitoring Siswa | Profil Poin Siswa |
| Mapping Guru Mengajar | Pemetaan Guru Mengajar |
| Mapping Guru BK | Pemetaan Guru BK |
| Jurnal Mengajar Guru Tiap Mapel | Jurnal Mengajar Guru |
| Absensi Guru Tiap Mapel | Kehadiran Mengajar Guru |
| Rekap Absensi Bulanan | Rekap Absensi Kelas |
| Status WhatsApp Ortu | Notifikasi WhatsApp Ortu |
| Data Orang Tua | Akun Orang Tua |
| Surat — Dashboard / Buat Baru / Semua | Surat BK › Ringkasan · Buat Surat · Draft · Arsip · Semua Surat |
| Rekapitulasi | Rekapitulasi Kepatuhan |

Judul di dalam halaman (`@section('title')`) ikut disamakan, jadi nama di
sidebar = judul halaman = breadcrumb. Tidak ada lagi istilah berbeda untuk
barang yang sama.

### 2.4 Sidebar & header dirombak
* Judul bagian (Utama, Monitoring, Data Master, …) sebagai pemisah visual.
* **Kotak pencarian menu** — ketik "absensi", menu yang cocok langsung
  tersaring (grup ikut terbuka otomatis).
* Grup yang sedang dibuka **diingat** lewat `localStorage`, tidak menutup lagi
  setiap pindah halaman.
* Tombol tutup sidebar di layar HP + area gelap yang bisa diklik.
* Header: **breadcrumb** berisi jalur menuju halaman (Beranda › Bagian › Grup).
  Nama halamannya sendiri tidak diulang di breadcrumb karena sudah tampil
  besar sebagai judul halaman tepat di bawahnya, badge periode akademik
  (merah + gembok kalau periode terkunci), dan **menu pengguna** berisi nama,
  email, jabatan, periode aktif, NIP, serta tombol Keluar.
* Setiap halaman kini punya **judul besar + kalimat penjelas** yang seragam,
  diambil otomatis dari registry menu. Halaman bisa menambah tombol aksi lewat
  `@section('aksi')` dan menyembunyikan judul lewat `@section('tanpa-judul')`.
* Notifikasi sukses/gagal jadi komponen `.alert` yang bisa ditutup.


### 2.5 Tombol Cetak kini benar-benar mencetak satu bagian saja

`cetakBagian()` dulu hanya menyembunyikan elemen ber-class `print-section`
*lain*. Isi halaman di luar print-section — kartu ringkasan, identitas,
filter, kotak status — tetap ikut tercetak kecuali halamannya rajin menandai
satu per satu dengan `no-print`. Sekarang fungsi itu menelusuri dari elemen
yang dicetak naik sampai `<body>` dan menyembunyikan semua elemen sebelahnya
di tiap tingkat, sehingga yang tercetak hanya bagian yang tombol Cetak-nya
ditekan — berlaku di semua halaman yang punya tombol Cetak, tanpa perlu
menandai elemen lain satu per satu.

---

## 3. Bug yang ditemukan & diperbaiki

1. **Role TU tidak bisa memakai aplikasi sama sekali.**
   `DashboardController` me-*redirect* TU ke `surat.dashboard`, padahal route
   itu dibatasi `role:guru_bk,admin` → setiap TU login langsung kena **403**.
   Sekarang TU punya dashboard sendiri (`dashboard/tu.blade.php`) berisi
   ringkasan master Jenis Surat + jumlah pemakaian tiap jenis.

2. **Kepala Sekolah disodori tautan yang berujung 403.**
   Dashboard memakai tautan `users.index`, `siswa.index`, `kelas.index`, dan
   checklist setup yang semuanya khusus Admin/Kurikulum. Sekarang setiap
   tautan dashboard dicek dulu lewat `Navigasi::bolehAkses()`, dan checklist
   setup hanya tampil untuk Admin.

3. **Ikon/warna "hilang" & CSS raksasa.**
   Safelist Tailwind sebelumnya memakai pola regex yang ikut membangkitkan
   seluruh varian opacity → file CSS hasil build **1,9 MB** (memperlambat
   semua halaman). Safelist diganti daftar eksplisit berisi kombinasi yang
   benar-benar dipakai → **±52 KB**, warna dinamis tetap aman.

4. **Font Awesome dimuat dua kali** (self-host lewat Vite + CDN cadangan).
   Blok CDN dihapus; ikon sudah di-*bundle* dan file build-nya disertakan.

5. **Kelas "sudah diabsen" dihitung dua kali** lewat dua query berbeda
   (`kelasSudahDiabsenIds` dan `terisiPerKelas`) padahal jawabannya sama.
   Sekarang satu query saja.

---

## 4. Dashboard dirombak per role

Semua dashboard sekarang memakai bahasa visual yang sama: kartu ringkasan →
kondisi hari ini → tabel detail → **Aksi Cepat**.

* **Admin & Kepala Sekolah** — kartu siswa/guru/rombel/jurnal, rekap kehadiran
  hari ini, **cincin persentase kepatuhan jurnal**, progres pengisian absensi
  per kelas (dengan bar), tren kehadiran 7 hari, aksi cepat sesuai hak akses,
  checklist setup (Admin saja), aktivitas terbaru (jurnal + kasus BK + siswa).
* **Kurikulum** — tambahan panel **"Guru Belum Mengisi Jurnal Hari Ini"**
  (inti monitoring guru: guru yang punya jadwal hari ini tapi belum mengisi),
  dihitung dengan 3 query untuk seluruh guru, bukan per guru.
* **Guru / Wali Kelas** — ringkasan "sesi hari ini / sudah diisi / belum diisi
  / jurnal bulan ini", kartu jadwal yang jelas menandai sesi belum terisi,
  panel wali kelas, daftar siswa Alfa, jurnal terakhir.
* **Guru BK** — kelas binaan, Alfa hari ini, kasus bulan ini, siswa sedang
  dibina, peringatan pemanggilan orang tua yang menunggu hasil, aksi cepat
  (catat kasus, buat surat, dsb).
* **Kesiswaan** — siswa aktif, Alfa hari ini, kelas bermasalah, kasus bulan
  ini, kondisi per kelas, aksi cepat.
* **Tata Usaha** — dashboard baru (lihat bug no. 1).

---

## 5. Berkas yang ditambah / diubah

**Baru**
* `app/Support/Navigasi.php` — registry menu, breadcrumb, hak akses tautan.
* `resources/views/partials/sidebar.blade.php` — sidebar data-driven.
* `resources/views/components/panel.blade.php` — kartu berjudul seragam.
* `resources/views/components/aksi-cepat.blade.php` — tombol aksi cepat.
* `resources/views/dashboard/tu.blade.php` — dashboard Tata Usaha.

**Diubah**
* `resources/views/layouts/app.blade.php` — header, breadcrumb, judul halaman,
  notifikasi, footer.
* `resources/css/app.css` — sistem komponen (kartu, tombol, input, tabel,
  alert, sidebar). Blok aturan **Cetak/Print** lama dipertahankan apa adanya.
* `tailwind.config.js` — safelist eksplisit.
* `app/Http/Controllers/DashboardController.php` — dipecah per role, tambah TU.
* `resources/views/dashboard/*.blade.php` — semua ditulis ulang.
* `resources/views/components/stat-card.blade.php` — dukung `hint` & `href`.
* `resources/views/auth/login.blade.php` — nama sekolah otomatis, tombol
  lihat kata sandi.
* 14 view lain — penyesuaian `@section('title')` agar seragam.

**Dihapus**
* `resources/views/components/nav-link|nav-group|nav-sublink.blade.php`
  (digantikan sidebar data-driven).
* `resources/views/ortu/index.blade.php` (sisa lama, tidak dipakai controller
  mana pun; portal orang tua memakai `resources/views/orangtua/`).

---

## 6. Catatan keamanan

File `.env` **tidak** disertakan dalam paket hasil perbaikan — gunakan `.env`
yang sudah ada di server Anda. Perlu diketahui: `.env` yang ikut terkirim di
arsip sebelumnya memuat token Fonnte dan `APP_KEY` asli. Sebaiknya token
tersebut dirotasi. Selain itu `APP_DEBUG` masih `true`; di server produksi
harus `false`.

---

# Penyederhanaan Alur BK (2026-08-28)

Keluhan: menu BK terlalu banyak sehingga pengguna bingung harus mulai dari mana,
dan mengubah status pembinaan menjadi "Selesai" terlalu berbelit.

## 1. Menu: 7 sub-menu → 3

| Sebelum | Sesudah |
|---|---|
| Ringkasan Pelanggaran | **Ringkasan BK** |
| Kasus & Pelanggaran | **Siswa Bimbingan** — halaman kerja harian |
| Pembinaan Siswa | **Buku Catatan BK** — 1 halaman, 4 tab |
| Pengurangan Poin | ↳ tab Kasus & Pelanggaran |
| Pemanggilan Orang Tua | ↳ tab Pembinaan |
| Profil Poin Siswa | ↳ tab Pengurangan Poin |
| Master Jenis Pelanggaran | ↳ tab Pemanggilan Orang Tua |
| | *(Master pindah ke grup **Pengaturan** › Jenis Pelanggaran)* |

**Rutenya sengaja tidak diubah sama sekali** (`bk.kasus.index`, `bk.pembinaan.index`,
dst). Yang berubah hanya sidebar + tambahan bar tab lewat komponen
`<x-bk-tab-catatan />` di keempat halaman. Semua tautan lama, penyaring, dan tombol
cetak tetap bekerja apa adanya.

Tab yang tampil mengikuti hak akses lama: Guru mapel hanya berkepentingan pada
Kasus, jadi baginya bar tab disembunyikan (cuma 1 tab yang berhak).

## 2. Status: satu tombol, dua keadaan

Sebelumnya status **hanya bisa diubah dari satu tempat** — dropdown kecil di dalam
tabel Riwayat pada halaman Profil Perilaku Siswa. Di daftar Kasus & Pembinaan,
statusnya cuma badge yang tidak bisa diklik. Menandai satu pembinaan selesai perlu
±8 langkah.

Sekarang:

- Pengguna hanya melihat **dua keadaan**: `Belum Selesai` / `Selesai`.
  Empat nilai status kasus (Baru/Diproses/Dalam Pembinaan/Selesai) tetap ada di
  database untuk laporan, tapi **diisi sistem** — otomatis jadi "Dalam Pembinaan"
  begitu pembinaan dicatat.
- Tombol `Tandai Selesai` / `Buka Kembali` tersedia di **empat tempat**: Ringkasan
  BK, daftar Kasus, daftar Pembinaan, dan Profil Perilaku Siswa — lewat satu
  komponen `<x-bk-tombol-selesai />` supaya bentuk & perilakunya identik.
- Penyaring status di kedua daftar ikut memakai istilah yang sama.

### Perbaikan bug yang ditemukan sekalian

Kaitan kasus ↔ pembinaan dulu hanya jalan **satu arah**: menandai selesai ikut
menyelesaikan pasangannya, tapi **membuka kembali tidak**. Akibatnya pembinaan bisa
berstatus belum selesai sementara kasusnya masih tertulis "Selesai" — dua halaman
menampilkan hal yang bertentangan. Sekarang dua arah
(`BkPembinaanController::update`).

## 3. Pencatatan dipermudah

- Tombol **Catat Pelanggaran** kini seragam namanya dan tampil menonjol di
  Ringkasan BK, Siswa Bimbingan, daftar Kasus, dan Profil siswa (dulu bernama
  "Tambah Kasus" / "+ Catat Kasus Baru" / "+ Catat Pelanggaran" — tiga istilah
  untuk satu hal yang sama).
- **Pencarian di Siswa Bimbingan sekarang menjangkau seluruh siswa aktif.** Dulu
  daftarnya hanya memuat siswa yang sudah punya kasus, sehingga mencatat
  pelanggaran *pertama* seorang siswa tidak bisa dimulai dari halaman ini. Daftar
  bawaannya tetap ringkas (hanya yang punya riwayat); begitu nama diketik,
  pencarian mencakup semua siswa.

## 4. Berkas

| Berkas | Isi |
|---|---|
| `resources/views/components/bk-tab-catatan.blade.php` | Bar tab Buku Catatan BK |
| `resources/views/components/bk-tombol-selesai.blade.php` | Tombol status satu klik |
| `app/Models/KasusSiswa.php`, `PembinaanSiswa.php` | Helper dua-keadaan (`isSelesai`, `labelStatusRingkas`, dll) |
| `app/Support/Navigasi.php` | Struktur menu baru |

## 5. Lanjutan: pencatatan dipusatkan & Laporan Bulanan BK

### Profil Perilaku Siswa jadi halaman BACA saja

Keempat tombol pencatatan (`+ Catat Pelanggaran`, `+ Catat Pembinaan`,
`+ Kurangi Poin`, `+ Panggil Ortu`) beserta tiga modalnya **dihapus** dari halaman
Profil Perilaku Siswa. Halaman itu dulu merangkap dua peran — tempat mencatat
sekaligus tempat membaca riwayat — dan tombolnya juga muncul di menu lain, sehingga
pengguna bingung harus mencatat dari mana.

Sekarang halaman itu murni untuk **membaca rekam jejak** seorang siswa (ringkasan
poin, riwayat, tombol status, cetak). Dua query yang dulu hanya dipakai modal
(`$jenisList`, `$kasusAktifTerbuka`) ikut dibuang.

### Dua halaman pencatatan yang sebelumnya TIDAK ADA

Pembinaan dan Pengurangan Poin ternyata **tidak punya halaman pencatatan sama
sekali** — satu-satunya jalan adalah modal di Profil Perilaku Siswa (daftar
Pengurangan bahkan menuliskan "buka profil siswa terkait"). Menghapus tombolnya
begitu saja akan membuat kedua pencatatan itu mustahil dilakukan. Maka dibuat:

| Route baru | Tombol pemicu |
|---|---|
| `bk.pembinaan.create` | Buku Catatan BK › tab Pembinaan › **Catat Pembinaan** |
| `bk.pengurangan.create` | Buku Catatan BK › tab Pengurangan Poin › **Kurangi Poin** |

Keduanya memakai langkah yang sama dengan Pemanggilan Orang Tua — cari siswa dulu,
baru isi formulir — lewat komponen bersama `<x-bk-pilih-siswa />`. Jadi keempat
pencatatan BK kini berpangkal dari satu tempat dengan alur yang seragam.

### Laporan Bulanan BK

Dulu ada tautan "Laporan Bulanan" di Menu Cepat, tapi hanya mengarah ke daftar
kasus biasa — laporannya sendiri tidak pernah ada. Sekarang tersedia sungguhan
sebagai **tab kelima** di Buku Catatan BK (`bk.laporan-bulanan`), sehingga jumlah
menu BK tetap tiga.

Isinya empat bagian, siap dicetak untuk Kepala Sekolah:

- **A. Rekap kegiatan** — kasus, kasus selesai, pembinaan, pengurangan poin,
  pemanggilan ortu, lengkap dengan keterangan tiap baris.
- **B. Sebaran pelanggaran** — menurut kategori (Ringan…Sangat Berat) beserta
  persentasenya, dan lima jenis pelanggaran terbanyak.
- **C. Rekap per kelas** — kelas mana yang paling perlu perhatian.
- **D. Peserta didik yang ditangani** — digabung dari keempat jenis catatan, jadi
  siswa yang bulan itu hanya dibina (tanpa kasus baru) tetap muncul.

Di layar juga ada pembanding dengan bulan sebelumnya (naik/turun berapa kasus).
Kasus dihitung menurut **tanggal kejadian**; pembinaan/pengurangan/pemanggilan
menurut **tanggal pelaksanaan**. Semuanya memakai `App\Support\RentangBulan` yang
sama dengan tab-tab di sebelahnya, jadi angkanya tidak akan pernah berbeda untuk
bulan yang sama.

| Berkas | Isi |
|---|---|
| `app/Http/Controllers/BkLaporanBulananController.php` | Penyusun laporan |
| `resources/views/bk/laporan-bulanan.blade.php` | Tampilan & cetakan |
| `resources/views/components/bk-pilih-siswa.blade.php` | Langkah "cari & pilih siswa" bersama |
| `resources/views/bk/{pembinaan,pengurangan}/create.blade.php` | Form pencatatan baru |
