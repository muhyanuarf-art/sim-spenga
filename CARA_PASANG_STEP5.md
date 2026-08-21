# STEP 5 — Kelas Berdasarkan Tahun Ajaran & Histori

Lanjutan dari STEP 1-4. **Ini perubahan struktural terbesar sejauh
ini** — tabel `kelas` sekarang terikat Tahun Ajaran. Pasang setelah
STEP 1-4 sudah terpasang & migrasinya sudah dijalankan.

## PENTING — File yang DIHAPUS dari STEP 4

STEP 5 membuat 1 file dari STEP 4 menjadi TIDAK TERPAKAI:

```
app/Models/WaliKelasHistori.php   → HAPUS file ini dari project Anda
```

Alasan: tabel `wali_kelas_histori` (STEP 4) sekarang REDUNDAN karena
`kelas` sudah punya `tahun_ajaran_id` sendiri — setiap tahun ajaran
otomatis punya baris kelas terpisah, jadi wali kelas SUDAH otomatis
terpisah per tahun tanpa perlu tabel tambahan. Migrasi
`2026_08_20_000006` di paket ini akan men-drop tabelnya di database.

## Isi paket (28 file)

```
BARU:
  database/migrations/2026_08_20_000005_add_tahun_ajaran_to_kelas_table.php
  database/migrations/2026_08_20_000006_drop_wali_kelas_histori_table.php

TIMPA:
  app/Models/Kelas.php
  app/Models/User.php
  app/Http/Controllers/KelasController.php
  app/Http/Controllers/KenaikanKelasController.php
  app/Http/Controllers/JadwalController.php
  app/Http/Controllers/GuruMengajarController.php
  app/Http/Controllers/GuruBkController.php
  app/Http/Controllers/SiswaController.php
  app/Http/Controllers/WaliKelasController.php
  app/Http/Controllers/DashboardController.php
  app/Http/Controllers/RekapController.php
  app/Http/Controllers/NotifikasiWhatsappController.php
  app/Http/Controllers/OrangTuaController.php
  app/Http/Controllers/BkKasusController.php
  app/Http/Controllers/BkPemanggilanController.php
  app/Http/Controllers/BkPembinaanController.php
  app/Http/Controllers/BkPenguranganPoinController.php
  app/Http/Controllers/BkSiswaController.php
  app/Imports/KelasImport.php
  app/Imports/SiswaImport.php
  app/Imports/GuruMengajarImport.php
  app/Imports/JadwalImport.php
  routes/web.php   ⚠️ lihat catatan di bawah
  resources/views/kelas/index.blade.php
  resources/views/kelas/import.blade.php
  resources/views/kenaikan-kelas/index.blade.php
```

## PENTING — routes/web.php

Perubahan STEP 5 di file ini hanya **1 baris baru**:

```php
Route::post('kelas-salin', [KelasController::class, 'salinDariTahunAjaran'])->name('kelas.salin');
```

Ditambahkan tepat setelah baris `kelas.import`. Kalau Anda sudah
punya modifikasi lain di file ini, cukup tambahkan 1 baris ini secara
manual daripada menimpa seluruh file.

## Cara pasang

1. **Backup database Anda — WAJIB**, ini migrasi struktural terbesar
   sejauh ini.
2. Hapus `app/Models/WaliKelasHistori.php` dari project Anda.
3. Timpa/salin semua file di atas.
4. Jalankan migrasi:
   ```
   php artisan migrate
   ```
   Migrasi `2026_08_20_000005` akan:
   - Menambah kolom `tahun_ajaran_id` (nullable) & `status` ke `kelas`.
   - Mem-backfill SEMUA baris kelas yang ada sekarang ke Tahun Ajaran
     yang **sedang aktif** saat migrasi dijalankan.
   - Mengganti unique constraint dari `nama_kelas` saja menjadi
     `(tahun_ajaran_id, tingkat, nama_kelas)`.

   Migrasi `2026_08_20_000006` akan menghapus tabel
   `wali_kelas_histori` (aman, sudah digantikan sepenuhnya).

   **Tidak ada data siswa/guru/jadwal/jurnal/absensi/BK yang hilang**
   — kelas_id yang sudah ada di semua tabel itu tetap menunjuk ke
   baris kelas yang sama persis seperti sebelumnya.

5. Buka menu **Data Kelas** — sekarang ada pemilih Tahun Ajaran di
   atas, dan tombol **"📋 Salin Struktur Kelas"** untuk menyalin nama
   kelas & tingkat dari tahun ajaran lain (Wali Kelas TIDAK ikut
   disalin, harus diatur ulang).

## Fitur baru yang bisa dicek

- **Data Kelas**: kelas sekarang per Tahun Ajaran. "7A" di 2026/2027
  dan "7A" di 2027/2028 adalah 2 baris/ID yang benar-benar berbeda.
- **Kenaikan Kelas**: Kelas Asal HANYA dari tahun ajaran aktif, Kelas
  Tujuan HANYA dari tahun ajaran tujuan (tidak bisa lagi salah pilih
  kelas dari tahun yang salah — baik lewat dropdown maupun lewat
  request langsung, sudah divalidasi di backend).
- **Wali Kelas**: mengubah wali kelas tahun ajaran baru TIDAK PERNAH
  menyentuh baris kelas tahun ajaran lama (karena memang baris yang
  berbeda) — tidak perlu tabel histori terpisah lagi.
- **Guru Mengajar & Jadwal & Guru BK**: dropdown kelas & validasi
  backend sekarang menolak kombinasi kelas dari tahun ajaran yang
  salah.
- **Import Excel** (Kelas, Siswa, Guru Mengajar, Jadwal): kode_kelas
  sekarang dicocokkan dalam konteks tahun ajaran yang relevan (kelas
  harus sudah dibuat untuk tahun ajaran itu dulu).

## Testing yang disarankan (sesuai brief STEP 5)

1. Buat kelas "7A" untuk 2026/2027 dan "7A" lagi untuk 2027/2028 →
   pastikan 2 ID kelas berbeda muncul di database.
2. Atur wali kelas 7A-2026/2027 = Guru A, wali kelas 7A-2027/2028 =
   Guru B → buka lagi Data Kelas untuk 2026/2027, pastikan masih
   Guru A.
3. Jalankan Kenaikan Kelas Ahmad dari 7A (2026/2027) ke 8A
   (2027/2028) → cek di Data Siswa hanya ada 1 baris Ahmad.
4. Buka Riwayat Kelas Ahmad → pastikan menampilkan 2026/2027→7A dan
   2027/2028→8A.
5. Buat mapping Guru Mengajar untuk 7A tahun 2026/2027 → buat kelas
   7A tahun 2027/2028 → pastikan mapping tahun 2026/2027 TIDAK
   berubah sama sekali.
6. Buat jadwal baru tahun 2027/2028 → pastikan jadwal 2026/2027 tetap
   sama persis.
7. Cek Dashboard saat periode aktif 2027/2028 → pastikan hanya
   menampilkan kelas 2027/2028, bukan gabungan semua tahun.
8. Coba buat "7A" dua kali untuk tahun ajaran yang sama → harus
   ditolak (unique constraint + validasi).
9. Ubah wali kelas 7A tahun 2027/2028 lagi → pastikan wali kelas 7A
   tahun 2026/2027 (dari test #2) tetap Guru A.

## Catatan

- `kelas.tahun_ajaran_id` **selalu** menunjuk baris **Semester
  Ganjil** tahun ajaran tsb (konvensi yang sama dipakai sejak STEP 4
  untuk `riwayat_kelas_siswas`) — karena satu kelas dipakai lintas
  Semester 1 & 2 dalam tahun ajaran yang sama. Detail lengkap ada di
  komentar migrasi `2026_08_20_000005...`.
- Histori wali kelas **sebelum** STEP 5 dipasang tidak bisa
  direkonstruksi sempurna kalau kelas yang sama pernah dipakai lintas
  tahun ajaran SEBELUM perbaikan ini — datanya memang tidak pernah
  dipisah per tahun sebelum ini. Backfill migrasi menempatkan SEMUA
  kelas yang ada sekarang ke tahun ajaran yang aktif SAAT MIGRASI
  DIJALANKAN. Ke depan (mulai sekarang), semuanya sudah terpisah
  dengan benar per tahun ajaran.
- Kolom `status` (aktif/nonaktif) pada kelas sudah disiapkan di
  database (default 'aktif') tapi belum ada UI togglenya — sesuai
  instruksi brief STEP 5 yang meminta "evaluasi apakah dibutuhkan",
  bukan mewajibkan fitur UI penuh untuk itu.
