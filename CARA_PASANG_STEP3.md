# STEP 3 — Pergantian Semester (Semester 1 → Semester 2)

Paket ini LANJUTAN dari STEP 1 & STEP 2 — pasang setelah keduanya
sudah terpasang & migrasinya sudah dijalankan.

Struktur folder di dalam zip sama persis dengan project Anda.

## Isi paket (5 file)

```
TIMPA:
  app/Models/TahunAjaran.php
  app/Http/Controllers/TahunAjaranController.php
  resources/views/tahun-ajaran/index.blade.php
  routes/web.php   ⚠️ lihat catatan di bawah

BARU:
  database/migrations/2026_08_20_000003_add_diaktifkan_fields_to_tahun_ajarans_table.php
```

## PENTING — routes/web.php

Sama seperti paket STEP 2: kalau Anda sudah punya perubahan lain di
file ini sejak STEP 2, **jangan timpa mentah-mentah**. Yang berubah
dari STEP 3 hanya **1 baris baru** di dalam grup
`Route::middleware('role:kurikulum,admin')` → `tahun-ajaran`:

```php
Route::post('tahun-ajaran/{tahunAjaran}/ganti-semester', [TahunAjaranController::class, 'gantiSemester'])->name('tahun-ajaran.ganti-semester');
```

Ditambahkan tepat setelah baris `tahun-ajaran.buka-kunci`. Kalau ragu,
cukup salin 1 baris ini ke file Anda.

(File `web.php` di paket ini juga sudah saya bersihkan dari komentar
TODO usang soal `duplikasiMapping()` yang sebenarnya sudah
diimplementasikan — aman diabaikan kalau Anda sudah membersihkannya
sendiri.)

## Cara pasang

1. Backup project & database Anda.
2. Timpa/salin semua file di atas (perhatikan catatan `routes/web.php`).
3. Jalankan migrasi:
   ```
   php artisan migrate
   ```
   Migrasi ini ADITIF — hanya menambah kolom `diaktifkan_at` dan
   `diaktifkan_oleh_id` ke tabel `tahun_ajarans` (jejak sederhana
   siapa & kapan sebuah periode diaktifkan — lihat Bagian 16 di
   prompt STEP 3). Tidak ada data yang dihapus.
4. Buka menu **Pengaturan → Tahun Ajaran**. Sekarang ada kartu
   **"Periode Aktif"** di paling atas halaman:
   - Kalau periode aktif adalah **Semester Ganjil** dan **Semester
     Genap** di tahun ajaran yang sama sudah dibuat (dan belum aktif),
     akan muncul tombol **"🔁 Tutup Semester Ganjil & Aktifkan
     Semester Genap"**.
   - Kalau Semester Genap belum dibuat, akan muncul pesan info
     (bukan tombol) yang mengarahkan untuk membuatnya dulu di tabel
     bawah.
   - Kalau jadwal Semester Genap belum ada, muncul info tambahan yang
     mengarahkan ke tombol "📋 Salin Mapping Guru/Jadwal" yang sudah
     ada dari STEP 1.

## Testing yang disarankan (sesuai brief STEP 3)

1. Pastikan Semester 1 (Ganjil) AKTIF, Semester 2 (Genap) sudah dibuat
   (AKAN DATANG) di tahun ajaran yang sama.
2. Klik **"Tutup Semester Ganjil & Aktifkan Semester Genap"** →
   konfirmasi.
3. Cek hasil: Semester 1 → badge "Selesai" + 🔒 Terkunci. Semester 2 →
   badge "Aktif".
4. Buka Rekap/Dashboard/Laporan → data Semester 1 harus tetap ada dan
   bisa dilihat.
5. Buat jurnal/absensi baru → harus masuk ke Semester 2, bukan
   Semester 1 (otomatis, karena semua modul memakai periode aktif
   yang sama — tidak ada yang perlu diubah manual satu-satu).
6. Coba edit jurnal/jadwal/mapping milik Semester 1 → harus ditolak
   (mekanisme STEP 2, tidak berubah).
7. Coba jalankan pergantian semester lagi dari halaman lama yang
   masih terbuka di 2 tab / klik tombol dua kali cepat → sistem harus
   menolak permintaan kedua dengan pesan "sudah diproses sebelumnya",
   BUKAN membuat dua periode aktif sekaligus.
8. Coba jalankan `POST /tahun-ajaran/{id}/ganti-semester` pada
   Semester 2 (Genap) yang sedang aktif → harus ditolak dengan pesan
   bahwa pergantian ke tahun ajaran berikutnya belum tersedia (STEP 4).
9. Cek jadwal Semester 2 (kalau sudah ada sebelumnya) tidak
   terhapus/tertimpa oleh proses pergantian.

## Catatan

- STEP 4 (kenaikan kelas, pembuatan tahun ajaran berikutnya,
  perpindahan siswa ke kelas baru) TIDAK termasuk paket ini — sesuai
  batasan yang diminta, sengaja tidak disentuh sama sekali.
- Tombol "Tutup Semester" per-baris dan "Buka Kembali" dari STEP 2
  tetap ada dan tidak berubah — tombol baru di STEP 3 ini cuma
  jalan pintas untuk kasus paling umum (Ganjil → Genap, tahun ajaran
  sama).
