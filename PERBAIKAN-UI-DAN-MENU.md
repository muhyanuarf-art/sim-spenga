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
