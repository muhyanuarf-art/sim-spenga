# STEP 1 — Fondasi Tahun Ajaran, Semester, Periode Aktif

Paket ini berisi file-file yang PERLU DITIMPA di project Laravel
SIM-SPENGA Anda. Struktur folder di dalam zip ini SAMA PERSIS dengan
struktur project Anda, jadi tinggal ekstrak dan timpa di root project.

## Isi paket (6 file)

```
app/Models/TahunAjaran.php                     (TIMPA — sudah ada)
app/Http/Controllers/TahunAjaranController.php (TIMPA — sudah ada)
app/Support/PeriodeAkademik.php                (BARU — file baru)
resources/views/tahun-ajaran/index.blade.php   (TIMPA — sudah ada)
database/migrations/2026_08_20_000001_add_periode_fields_to_tahun_ajarans_table.php
                                                (BARU — file baru)
database/seeders/TahunAjaranSeeder.php         (TIMPA — sudah ada)
```

## Cara pasang

1. Backup dulu project & database Anda (jaga-jaga).
2. Ekstrak zip ini, lalu salin/timpa 6 file di atas ke lokasi yang
   sama persis di project Anda (root project Laravel).
3. Jalankan migrasi:
   ```
   php artisan migrate
   ```
   Migrasi ini ADITIF — hanya menambah kolom `tanggal_mulai`,
   `tanggal_selesai`, `status` ke tabel `tahun_ajarans` yang sudah
   ada. Tidak ada data yang dihapus. Baris yang sedang `is_active =
   true` otomatis diisi `status = 'aktif'` saat migrasi jalan.
4. (Opsional) kalau mau contoh data awal:
   ```
   php artisan db:seed --class=TahunAjaranSeeder
   ```
5. Buka menu **Pengaturan → Tahun Ajaran** dan cek:
   - Form tambah/edit sekarang ada input Tanggal Mulai & Tanggal
     Selesai.
   - Kolom tabel baru: Tanggal & Status.
   - Tombol Aktifkan / Kunci / Buka Kunci / Hapus / Salin Mapping
     tetap seperti sebelumnya, tidak berubah perilakunya.

## Testing yang disarankan (sesuai brief STEP 1)

1. Buat Tahun Ajaran 2026/2027 (Ganjil & Genap).
2. Aktifkan 2026/2027 - Ganjil → cek periode aktif muncul benar di
   header/dashboard.
3. Buat 2027/2028 (Ganjil & Genap) → pastikan data 2026/2027 tetap
   ada dan tidak berubah.
4. Pastikan hanya SATU baris yang berstatus Aktif di satu waktu.
5. Cek dashboard & modul lama (Jadwal, Jurnal, Absensi, BK, Kenaikan
   Kelas, Riwayat Kelas) tidak error.

## Catatan

- STEP 2 (lock/penutupan semester menyeluruh) TIDAK termasuk dalam
  paket ini — sesuai instruksi, itu dikerjakan terpisah.
- Mekanisme kunci per-baris (`terkunci`, `terkunci_at`,
  `terkunci_oleh_id`) yang sudah ada sebelumnya di project Anda TIDAK
  diubah sama sekali oleh paket ini.
