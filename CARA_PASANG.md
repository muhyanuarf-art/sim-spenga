# Menghapus Fitur Kenaikan Kelas

Tidak ada migrasi baru — data lama (`riwayat_kelas_siswas`, dll) TIDAK
disentuh sama sekali, hanya cara memprosesnya yang berubah.

## PENTING — 2 FILE HARUS DIHAPUS, 1 FOLDER

```
app/Http/Controllers/KenaikanKelasController.php   → HAPUS
resources/views/kenaikan-kelas/   (seluruh folder)  → HAPUS
```

Kedua sudah digantikan sepenuhnya oleh file baru di paket ini.

## Isi paket (8 file)

```
BARU:
  app/Http/Controllers/RiwayatKelasController.php
  resources/views/riwayat-kelas/show.blade.php

TIMPA:
  app/Http/Controllers/OrangTuaDashboardController.php
  app/Http/Controllers/SiswaController.php
  app/Imports/SiswaImport.php
  routes/web.php   ⚠️ lihat catatan di bawah
  resources/views/layouts/app.blade.php
  resources/views/orangtua/dashboard.blade.php
```

## PENTING — routes/web.php

Perubahan di file ini:
- **Dihapus**: `kenaikan-kelas.index` (GET) dan `kenaikan-kelas.store` (POST).
- **Diubah**: route `siswa.riwayat-kelas` sekarang mengarah ke
  `RiwayatKelasController::show` (bukan lagi `KenaikanKelasController::riwayat`).
- **Diubah**: `use App\Http\Controllers\KenaikanKelasController;` menjadi
  `use App\Http\Controllers\RiwayatKelasController;`.

## Apa yang berubah

### 1. Menu "Kenaikan Kelas" dihapus total
Tidak ada lagi di sidebar, halaman, maupun route. Proses pindah kelas
massal lewat menu tersendiri sudah tidak ada.

### 2. Import Excel Data Siswa sekarang mencatat Riwayat Kelas otomatis
Ini bagian PALING PENTING — supaya histori tidak hilang. Setiap kali
Anda import Excel Data Siswa dan:
- **NIS-nya baru** → dicatat sebagai "Awal masuk" ke kelas yang diisi.
- **NIS-nya sudah ada** (siswa lama) dan `kode_kelas` di file BERBEDA
  dari kelas siswa itu sekarang → otomatis tercatat sebagai perpindahan
  kelas (kelas lama → kelas baru) untuk Tahun Ajaran yang sedang aktif.
- Import yang sama diulang tidak akan mencatat riwayat dobel.

Jadi alur kerja sekolah Anda sekarang: **setiap Tahun Ajaran baru,
cukup import ulang Excel Data Siswa dengan `kode_kelas` yang sudah
diperbarui** (siswa kelas 7 tahun lalu diisi kelas 8, dst) — riwayat
kenaikan kelasnya otomatis tercatat, tanpa perlu proses Kenaikan Kelas
manual.

### 3. Halaman "🕘 Riwayat Kelas" (admin) — TETAP ADA
Tombol di Data Siswa tetap berfungsi sama seperti sebelumnya,
menampilkan histori lengkap kelas siswa dari tahun ke tahun. Hanya
"mesin" di baliknya yang berganti (dari proses manual Kenaikan Kelas
menjadi otomatis dari Import Excel).

### 4. Portal Orang Tua sekarang menampilkan Riwayat Kelas (BARU)
Sebelumnya orang tua hanya bisa lihat rekap absensi & BK. Sekarang ada
kartu **"Riwayat Kelas"** di dashboard orang tua yang menampilkan
histori kelas anaknya dari tahun ke tahun (kelas asal → kelas
sekarang, per tahun ajaran) — persis permintaan Anda: *"orang tua
yang ingin melihat data anaknya yang sudah naik kelas."*

## Cara pasang

1. Timpa/tambahkan 8 file di atas.
2. **Hapus** `app/Http/Controllers/KenaikanKelasController.php`.
3. **Hapus** folder `resources/views/kenaikan-kelas/` beserta isinya.
4. Tidak perlu `php artisan migrate`.

## Testing yang disarankan

1. Cek sidebar — pastikan menu "Kenaikan Kelas" sudah tidak ada.
2. Buka `/kenaikan-kelas` langsung lewat URL → harus muncul halaman
   404 (route sudah tidak ada), bukan error 500.
3. Import Excel Data Siswa dengan NIS siswa yang SUDAH ADA di sistem,
   tapi `kode_kelas` diisi kelas yang BERBEDA dari kelas siswa itu
   sekarang → cek tombol "🕘 Riwayat Kelas" siswa tsb di Data Siswa →
   harus muncul baris riwayat baru (kelas lama → kelas baru).
2. Ulangi import Excel yang SAMA PERSIS lagi → pastikan riwayat TIDAK
   bertambah dobel.
3. Login sebagai orang tua siswa tsb → pastikan kartu "Riwayat Kelas"
   di dashboard menampilkan histori yang sama.
4. Cek data siswa & riwayat kelas yang SUDAH ADA sebelumnya (hasil
   proses Kenaikan Kelas lama) → pastikan semuanya masih utuh, baik
   di halaman admin maupun portal orang tua.
