# Revisi: KOP Surat lengkap — hanya muncul saat Cetak, diatur di Pengaturan Sekolah

## Apa yang berubah dari sebelumnya
1. **KOP Surat sekarang TIDAK tampil di layar biasa** — cuma muncul saat
   tombol "Cetak / Export PDF" ditekan (baik hasil print fisik maupun
   Save as PDF), persis seperti yang Anda minta. Sebelumnya salah, selalu
   tampil di layar.
2. **KOP Surat sekarang lengkap** sesuai contoh yang Anda kirim:
   - Logo kiri & kanan (upload gambar).
   - Baris "Pemerintah Daerah" (mis. PEMERINTAH KABUPATEN BREBES).
   - Baris "Instansi Induk" (mis. DINAS PENDIDIKAN PEMUDA DAN OLAHRAGA).
   - Baris "Unit Kerja" (mis. UPT SATUAN PENDIDIKAN FORMAL).
   - Nama Sekolah (besar, tebal — pakai kolom yang sudah ada).
   - Kecamatan.
   - Alamat Sekolah.
   - Email Sekolah (boleh lebih dari 1, dipisah " / ").
   - Garis pembatas ganda di bawahnya.

   Semua baris di atas OPSIONAL — yang tidak diisi otomatis tidak
   ditampilkan (bukan tampil kosong), jadi sekolah yang cuma isi
   sebagian tetap dapat KOP Surat rapi.
3. **Semua field ini bisa diatur di menu Pengaturan Sekolah** (Admin/
   Kurikulum) — sudah ditambahkan ke form yang sudah ada di sana.

## ⚠️ 2 LANGKAH WAJIB setelah pasang file — jangan dilewati salah satu

### 1. Migrasi database
```powershell
cd C:\laragon\www\sim-spenga
php artisan migrate
```

### 2. Buat symlink storage (supaya logo yang diupload bisa tampil)
Saya cek folder `public/storage` di project Anda — itu masih folder
kosong biasa, BUKAN symlink ke `storage/app/public`. Kalau ini belum
dibuat, logo yang diupload akan tersimpan tapi TIDAK BISA TAMPIL (gambar
rusak/blank). Jalankan:
```powershell
cd C:\laragon\www\sim-spenga
php artisan storage:link
```
(Aman dijalankan meski `public/storage` sudah ada sebagai folder biasa —
kalau muncul pesan folder sudah ada, hapus dulu folder `public/storage`
yang kosong itu, baru jalankan perintah ini lagi.)

### 3. `npm run build` (tetap wajib seperti sebelumnya)
KOP Surat masih pakai class-class baru (`cetak-saja`, dst.) yang belum
pernah dipakai sebelumnya:
```powershell
npm run build
```

## File yang ditambah/diubah
- `database/migrations/2026_08_24_000001_add_kop_surat_fields_to_pengaturan_sekolahs_table.php` **(baru)** — kolom baru di `pengaturan_sekolahs`: pemerintah_daerah, instansi_induk, unit_kerja, kecamatan, alamat_sekolah, email_sekolah, logo_kiri_path, logo_kanan_path (semua nullable).
- `app/Models/PengaturanSekolah.php` — kolom baru masuk `$fillable`, tambah helper `logoKiriUrl()` / `logoKananUrl()`.
- `app/Http/Controllers/PengaturanSekolahController.php` — `update()` sekarang menangani upload/hapus 2 logo (validasi gambar, maks 2MB, file lama otomatis dihapus saat diganti).
- `resources/views/pengaturan-sekolah/edit.blade.php` — form baru bagian "KOP Surat" (upload logo + semua baris teks), form diberi `enctype="multipart/form-data"` supaya upload file berfungsi.
- `resources/views/components/kop-surat.blade.php` — dirombak total: layout 2 logo + teks di tengah, dan class `cetak-saja` supaya SEMBUNYI di layar, HANYA tampil saat print.
- `resources/css/app.css` — aturan CSS baru `.cetak-saja` (default `display:none`, jadi `display:block` di `@media print`).

**Tidak perlu ubah file laporan lain** — karena `<x-kop-surat />` sudah
terpasang di 9 halaman itu sejak paket sebelumnya, cukup timpa komponennya
saja, otomatis berlaku di semua tempat.

## Cara menerapkan (di Laragon) — urutan lengkap
1. Salin semua file di atas ke lokasi yang sama persis di
   `C:\laragon\www\sim-spenga`.
2. `php artisan migrate`
3. `php artisan storage:link` (hapus dulu folder `public/storage` kosong kalau perlu, lihat catatan di atas)
4. `npm run build`
5. Buka menu **Pengaturan Sekolah** → isi bagian "KOP Surat" (upload logo, isi baris pemerintah/instansi/dst sesuai contoh Anda) → Simpan.
6. Buka salah satu laporan (mis. Rekap Absensi Kelas) → pastikan di LAYAR BIASA KOP Surat **tidak muncul** → klik "Cetak / Export PDF" → di jendela print/preview PDF, KOP Surat **harus muncul** di paling atas, lengkap dengan logo & garis pembatas ganda.
