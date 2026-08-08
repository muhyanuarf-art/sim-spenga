# Rekapitulasi Jurnal Mengajar — Format Bulanan (1–31)

## Apa yang berubah

1. **Format tabel** — "Kepatuhan Pengisian Jurnal" yang tadinya cuma
   1 angka per guru, sekarang jadi tabel bulanan tanggal 1–31 (mirip
   Rekap Absensi Bulanan Wali Kelas), dengan kolom akhir **Terisi**,
   **Seharusnya**, dan **%**.

2. **"Seharusnya" dihitung otomatis dari jadwal**, bukan angka tebak-
   tebakan:
   - Diambil dari jadwal pelajaran guru per **SESI mengajar** (jam
     berurutan = 1 sesi = 1 jurnal wajib), bukan per jam pelajaran.
   - Sesi yang jamnya Senin dihitung 1x untuk setiap tanggal yang
     jatuh pada hari Senin di bulan yang dipilih (otomatis
     menyesuaikan jumlah hari efektif per bulan, karena tiap bulan
     jumlah Senin-nya beda).

3. **Akses dibuka untuk Kepala Sekolah** — sebelumnya menu
   Rekapitulasi hanya bisa diakses Admin & Kurikulum. Sekarang
   Kepala Sekolah juga bisa melihatnya (view-only — tidak bisa edit
   jadwal/mapping, itu tetap khusus Kurikulum & Admin).

4. **Tanggal otomatis ikut jam server** — bulan & tahun default di
   filter SELALU memakai `now()` (tanggal server saat halaman
   dibuka), bukan angka tetap. Ditambah baris "📅 Hari ini: ..." yang
   menampilkan tanggal hari ini dalam format Indonesia penuh (mis.
   *Sabtu, 08 Agustus 2026*).

   ⚠️ Ini bergantung pada setting `APP_TIMEZONE` & `APP_LOCALE` di
   file `.env` project Anda. Defaultnya (`.env.example`) sudah benar:
   ```
   APP_TIMEZONE=Asia/Jakarta
   APP_LOCALE=id
   ```
   Kalau jam/tanggal di server ternyata masih meleset, cek nilai
   kedua baris ini di `.env` Anda (bukan `.env.example`), lalu jalankan:
   ```bash
   php artisan config:clear
   ```

## File yang diubah

| File | Keterangan |
|---|---|
| `app/Http/Controllers/RekapController.php` | Logic rekap bulanan baru (dihitung dari sesi, bukan jam) |
| `resources/views/rekap/index.blade.php` | Tabel tanggal 1–31 + info tanggal hari ini |
| `routes/web.php` | Route `rekap.index` dibuka untuk `admin,kurikulum,kepala_sekolah` |
| `resources/views/layouts/app.blade.php` | Menu "Rekapitulasi" di sidebar ikut muncul untuk Kepala Sekolah |

## Cara pasang

1. Salin 4 file di atas ke project Anda (timpa yang lama).
2. Tidak perlu migration.
3. Clear cache:
   ```bash
   php artisan route:clear
   php artisan view:clear
   ```
4. Test:
   - Login sebagai **Kepala Sekolah** → menu "Rekapitulasi" harus
     muncul di sidebar & bisa dibuka (sebelumnya tidak ada).
   - Login sebagai **Kurikulum/Admin** → buka Rekapitulasi, cek
     tabel guru menampilkan tanggal 1–31, dengan warna hijau di
     tanggal yang sudah lengkap terisi dan merah yang belum.
   - Cek 1 guru yang punya sesi 3 jam berurutan (Senin jam 1-3): di
     tabel, tanggal Senin harus menghitung **1 sesi** seharusnya
     (bukan 3), sesuai jumlah Senin di bulan itu.
   - Ganti bulan di dropdown → kolom "Seharusnya" per guru harus
     ikut berubah sesuai jumlah hari itu di bulan yang dipilih.
