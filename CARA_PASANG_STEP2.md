# STEP 2 — Penutupan & Penguncian Semester

Paket ini berisi file-file yang PERLU DITIMPA di project Laravel
SIM-SPENGA Anda, LANJUTAN dari paket STEP 1. Pasang paket ini
SETELAH paket STEP 1 sudah terpasang & migrasinya sudah dijalankan.

Struktur folder di dalam zip ini SAMA PERSIS dengan struktur project
Anda, jadi tinggal ekstrak dan timpa di root project.

## Isi paket (21 file)

```
TIMPA (sudah ada dari STEP 1 / project asli):
  app/Models/TahunAjaran.php
  app/Support/PeriodeAkademik.php
  app/Http/Controllers/TahunAjaranController.php
  app/Http/Controllers/GuruMengajarController.php
  app/Http/Controllers/JadwalController.php
  app/Http/Controllers/MengajarController.php
  app/Http/Controllers/BkKasusController.php
  app/Http/Controllers/BkPembinaanController.php
  app/Http/Controllers/BkPenguranganPoinController.php
  app/Models/AbsensiSiswa.php
  app/Models/JurnalMengajar.php
  app/Models/KasusSiswa.php
  app/Models/PembinaanSiswa.php
  app/Models/PenguranganPoinSiswa.php
  resources/views/tahun-ajaran/index.blade.php
  resources/views/kurikulum/guru-mengajar/index.blade.php
  resources/views/jadwal/index.blade.php
  resources/views/absensi/pilih-kelas.blade.php
  routes/web.php

BARU:
  database/migrations/2026_08_20_000002_add_dibuka_fields_to_tahun_ajarans_table.php
  resources/views/errors/423.blade.php
```

## PENTING — routes/web.php

File ini kemungkinan besar sudah Anda ubah sendiri sejak STEP 1
(menambah rute lain, dsb). **Jangan langsung timpa mentah-mentah**
kalau Anda punya perubahan lain di file ini. Yang berubah dari STEP 2
hanya 2 blok kecil:

1. Route group `kurikulum/guru-mengajar` — rute `store` dan `import`
   ditambah `->middleware('periode-aktif')`.
2. Route group `jadwal` — rute `store` dan `import` ditambah
   `->middleware('periode-aktif')`.

Kalau ragu, buka file `routes/web.php` di paket ini dan cari 2 baris
di atas untuk disalin manual ke file Anda, daripada menimpa
seluruhnya.

## Cara pasang

1. Backup dulu project & database Anda.
2. Timpa/salin semua file di atas ke lokasi yang sama di project Anda
   (cek catatan khusus `routes/web.php` di atas).
3. Jalankan migrasi:
   ```
   php artisan migrate
   ```
   Migrasi ini ADITIF — hanya menambah kolom `dibuka_at` dan
   `dibuka_oleh_id` ke tabel `tahun_ajarans`. Tidak ada data yang
   dihapus.
4. Buka menu **Pengaturan → Tahun Ajaran**, cek:
   - Tombol "🔒 Tutup Semester" pada periode yang belum terkunci.
   - Setelah ditutup: badge Status jadi "Selesai", badge Kunci jadi
     "🔒 Terkunci", tombol berubah jadi "🔓 Buka Kembali" (hanya
     tampil untuk Admin).
   - Semester lain TIDAK otomatis aktif setelah penutupan.

## Testing yang disarankan (sesuai brief STEP 2)

1. Pastikan ada Tahun Ajaran aktif dengan Semester 1 & Semester 2.
2. Guru isi jurnal & absensi di Semester 1 (harus berhasil).
3. Admin/Kurikulum klik "Tutup Semester" pada Semester 1 → konfirmasi.
4. Coba edit jurnal/absensi Semester 1 lagi → harus DITOLAK (baik dari
   tombol yang sudah disembunyikan, maupun dari URL langsung).
5. Coba edit/hapus Mapping Guru Mengajar & Jadwal milik Semester 1 →
   harus DITOLAK.
6. Coba batalkan/ubah status Kasus BK, update Pembinaan, atau batalkan
   Pengurangan Poin milik Semester 1 → harus DITOLAK.
7. Buka halaman Rekap/Dashboard → data Semester 1 harus TETAP ADA dan
   bisa dilihat (karena Semester 1 belum diganti is_active-nya).
8. Admin klik "Buka Kembali" pada Semester 1 → konfirmasi → sekarang
   Semester 1 bisa diedit lagi.
9. Pastikan Semester 2 TIDAK otomatis berubah statusnya kapan pun di
   atas — pergantian semester adalah pekerjaan STEP 3.

## Catatan

- STEP 3 (aktivasi/pergantian semester berikutnya, pemilih periode
  historis di Laporan/Rekap) TIDAK termasuk dalam paket ini.
- Modul Kenaikan Kelas SENGAJA tidak disentuh sama sekali di STEP 2
  ini (sesuai batasan yang diminta).
