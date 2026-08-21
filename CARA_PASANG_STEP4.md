# STEP 4 — Pergantian Tahun Ajaran & Kenaikan Kelas

Lanjutan dari STEP 1-3. Pasang setelah semuanya sudah terpasang &
migrasinya sudah dijalankan.

## Isi paket (11 file)

```
BARU:
  app/Models/WaliKelasHistori.php
  database/migrations/2026_08_20_000004_create_wali_kelas_histori_table.php

TIMPA:
  app/Models/TahunAjaran.php
  app/Models/Kelas.php
  app/Http/Controllers/TahunAjaranController.php
  app/Http/Controllers/KelasController.php
  app/Http/Controllers/KenaikanKelasController.php
  routes/web.php   ⚠️ lihat catatan di bawah
  resources/views/tahun-ajaran/index.blade.php
  resources/views/kenaikan-kelas/index.blade.php
  resources/views/kelas/index.blade.php
```

## PENTING — routes/web.php

Sama seperti paket sebelumnya: kalau sudah ada perubahan lain di file
ini, jangan timpa mentah-mentah. Perubahan STEP 4 di file ini:

1. Route baru: `POST tahun-ajaran-baru` → `tahun-ajaran.buat-baru`
   (ditambahkan tepat setelah `Route::resource('tahun-ajaran', ...)`).
2. Route `kenaikan-kelas.store`: middleware `periode-aktif` DIHAPUS
   (lihat penjelasan penting di bagian "Masalah yang ditemukan" pada
   laporan STEP 4 — proses ini menulis ke tahun ajaran BARU yang
   belum terkunci, bukan ke periode aktif lama yang mungkin sudah
   ditutup; proteksi lock yang benar sudah dipindah ke dalam
   controller).

## Cara pasang

1. Backup project & database Anda.
2. Timpa/salin semua file di atas.
3. Jalankan migrasi:
   ```
   php artisan migrate
   ```
   Migrasi ini ADITIF — membuat tabel baru `wali_kelas_histori` dan
   membackfill 1 baris histori per kelas (untuk tahun ajaran yang
   sedang aktif saat migrasi dijalankan). Tidak ada data lama yang
   diubah/dihapus.

## Fitur baru yang bisa dicek

Di halaman **Tahun Ajaran**:
- Kartu **"Tahun Ajaran Berikutnya"** — tombol satu-klik untuk
  membuat Semester 1 & Semester 2 tahun ajaran baru sekaligus (tanpa
  langsung aktif).
- Tombol **"✅ Aktifkan"** pada Semester 1 tahun ajaran baru sekarang
  otomatis DITOLAK kalau tahun ajaran yang masih aktif belum selesai
  & terkunci SEPENUHNYA (kedua semester).

Di halaman **Kenaikan Kelas**:
- Tahun ajaran tujuan sekarang **otomatis dihitung** dari periode
  aktif (tidak ada lagi dropdown bebas).
- Kelas Tujuan boleh SAMA dengan Kelas Asal — untuk mencatat siswa
  yang **tinggal kelas**.
- Preview sebelum submit sekarang menampilkan tahun asal, kelas
  asal, tahun tujuan, kelas tujuan, DAN daftar nama siswa satu per
  satu (bukan cuma jumlah).
- Siswa yang sudah tercatat naik kelas sebelumnya akan DILEWATI dan
  **disebutkan namanya** di pesan (bukan diam-diam dilewati).

Di halaman **Data Kelas**:
- Setiap kali Wali Kelas diubah, sistem mencatat histori untuk tahun
  ajaran yang sedang aktif — histori tahun ajaran sebelumnya TIDAK
  ikut berubah.

## Testing yang disarankan (sesuai brief STEP 4)

1. Buat Tahun Ajaran 2027/2028 lewat kartu baru → pastikan 2026/2027
   tetap ada & tidak berubah.
2. Jalankan Kenaikan Kelas dari 7A → 8A untuk beberapa siswa →
   pastikan ID siswa tidak berubah, riwayat tercatat dengan benar.
3. Coba proses siswa yang sama lagi → harus DILEWATI dengan nama
   disebutkan di pesan, bukan diam-diam / dua penempatan aktif.
4. Ubah Wali Kelas 8A untuk tahun ajaran 2027/2028 → pastikan histori
   wali kelas 2026/2027 (dicatat via backfill migrasi) tidak berubah.
5. Buat jadwal baru untuk 2027/2028 Semester 1 → pastikan jadwal
   2026/2027 Semester 2 tetap ada & tidak tertimpa (gunakan fitur
   "📋 Salin Mapping Guru/Jadwal" yang sudah ada dari STEP 1 kalau
   ingin menyalin, bukan menulis ulang manual).
6. Coba aktifkan 2027/2028 Semester 1 SEBELUM 2026/2027 Semester 2
   ditutup & dikunci → harus DITOLAK dengan pesan jelas.
7. Setelah 2026/2027 Semester 1 & 2 keduanya SELESAI/TERKUNCI, baru
   aktifkan 2027/2028 Semester 1 → harus BERHASIL, dan dashboard
   otomatis menampilkan periode baru tanpa perlu diubah manual di
   modul lain.

## Catatan

- Struktur tabel `kelas` SENGAJA dipertahankan global (tidak dipecah
  per tahun ajaran) — alasan lengkap ada di komentar migrasi
  `2026_08_20_000004...` dan laporan STEP 4. Satu-satunya bagian yang
  dipisah per tahun ajaran adalah Wali Kelas (lewat tabel baru), yang
  memang satu-satunya atribut kelas yang terbukti bermasalah lintas
  tahun (Bagian 15/16 & Test 5 di prompt STEP 4).
- Modul Kenaikan Kelas kelas 9 → lulus/keluar sekolah TIDAK dibuat di
  STEP 4 ini (sesuai instruksi eksplisit Bagian 24) — gunakan toggle
  `is_active` yang sudah ada di menu Data Siswa untuk menonaktifkan
  siswa yang lulus.
