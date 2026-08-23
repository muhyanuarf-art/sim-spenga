# Revisi: menu "Ekstrakurikuler" dikelompokkan untuk Admin

## Apa yang berubah
- **Role Kesiswaan**: TIDAK berubah — "Ekstrakurikuler" tetap tampil flat
  di sidebar (sesuai screenshot Anda yang sudah dianggap bagus).
- **Role Admin**: "Ekstrakurikuler" sekarang masuk grup dropdown baru
  **"Kesiswaan"** (pola sama seperti grup "Kurikulum" yang sudah ada) —
  supaya sidebar Admin yang sudah padat tetap rapi, dan gampang ditambah
  menu kesiswaan lain nanti (mis. kalau menu Anggota/Absensi Ekskul dibuat,
  tinggal ditambah sebagai sub-menu baru di grup ini juga).

Tidak ada perubahan pada route, controller, atau akses — murni tampilan
sidebar.

## File yang diubah
- `resources/views/layouts/app.blade.php`

## Cara menerapkan
1. Timpa file di atas ke `C:\laragon\www\sim-spenga\resources\views\layouts\app.blade.php`.
2. Tidak perlu migrasi, tidak perlu `npm run build`.
3. Test: login sebagai Admin → sidebar sekarang ada grup dropdown baru
   "Kesiswaan" berisi "Ekstrakurikuler". Login sebagai Kesiswaan → pastikan
   tampilannya tidak berubah dari sebelumnya (masih flat).
