# Cara Menerapkan Patch: Perbaikan Notifikasi WA Fonnte

Patch ini SUDAH KOSONGKAN dari perubahan `storage/` — bagian itu Anda
kerjakan manual dengan perintah di bagian 3, karena nama file cache
Blade acak dan beda tiap komputer (patch bisa gagal kalau dipaksakan).

## 1. Pull dulu (untuk jaga-jaga)
Dari root project (`C:\laragon\www\sim-spenga`):
```powershell
git status
```
Pastikan tidak ada perubahan lokal yang belum ter-commit (kalau ada,
`git stash` dulu supaya tidak konflik).

## 2. Terapkan patch kode
```powershell
git apply --whitespace=nowarn "$env:USERPROFILE\Downloads\perbaikan-notifikasi-wa-fonnte.patch"
```
Kalau file-nya Anda simpan di lokasi lain, cek dulu:
```powershell
Get-ChildItem -Path "$env:USERPROFILE\Downloads" -Filter "perbaikan-notifikasi-wa-fonnte*"
```
dan pakai path yang muncul di situ.

## 3. Bersihkan file storage secara manual
```powershell
git rm -r --cached storage/logs storage/framework/cache storage/framework/sessions storage/framework/testing storage/framework/views
git add storage/logs/.gitkeep storage/framework/cache/.gitkeep storage/framework/sessions/.gitkeep storage/framework/testing/.gitkeep storage/framework/views/.gitkeep
php artisan view:clear
```
(`.gitignore` sudah ikut diperbarui lewat patch di langkah 2, jadi
setelah ini file-file tsb tidak akan ke-track lagi ke depannya.)

## 4. Migrasi database
```powershell
php artisan migrate
```
Ini menambahkan kolom `percobaan_ke` dan `keterangan_gagal` ke tabel
`notifikasi_alfa_terkirims` (dan tetap membuat `failed_jobs` kalau
belum ada).

## 5. Commit & push (opsional, kalau mau disimpan ke GitHub lagi)
```powershell
git add -A
git commit -m "Perbaiki bug status Fonnte + retry maks 2x + bersihkan file lama"
git push
```

## 6. Jalankan & uji coba
```powershell
php artisan queue:work
```
Biarkan terminal ini tetap terbuka selama Anda menguji. Di tab/terminal
lain jalankan Laragon seperti biasa, lalu:

1. Login sebagai guru mapel → isi absensi, tandai 1 siswa "Alfa" yang
   nomor WA orang tuanya SUDAH diisi (menu Data Siswa).
2. Cek terminal `queue:work` — harus muncul log job
   `KirimNotifikasiAlfaWhatsapp` diproses.
3. Cek WhatsApp di nomor yang Anda isi — pesan harus masuk.
4. Buka menu **Status WhatsApp Ortu** (login sebagai wali kelas siswa
   tsb, atau admin) — status harus jadi "Terkirim".
5. Coba juga isi nomor asal-asalan (mis. `000000`) untuk 1 siswa lain,
   tandai Alfa, dan lihat di dashboard — setelah ± 2 menit percobaan
   ke-2 akan otomatis jalan, lalu kalau tetap gagal, status jadi
   "Gagal" dengan keterangan di kolom terakhir tabel.

**Catatan penting:** fitur kirim pesan ini SUDAH BISA jalan penuh di
localhost — tidak perlu domain publik/hosting dulu. Yang butuh domain
publik nanti hanya kalau Anda mau tambah fitur status "Diterima"/
"Dibaca" real-time lewat webhook Fonnte (belum diimplementasikan di
versi ini).
