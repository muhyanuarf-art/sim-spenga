# Fitur baru: Menu "Ekstrakurikuler" (input nama kegiatan) untuk Kesiswaan

## Apa yang dibuat
Menu master data sederhana — Kesiswaan bisa tambah/edit/hapus nama kegiatan
ekstrakurikuler (mis. Pramuka, PMR, Futsal), dengan pembina opsional (boleh
dikosongkan dulu kalau belum ditentukan) dan status Aktif/Nonaktif.

Ini adalah **langkah pertama** dari mekanisme absensi ekskul yang pernah
saya jelaskan (master kegiatan → anggota → jadwal → absensi → rekap).
Menu Anggota, Jadwal, dan Absensi ekskul BELUM dibuat di paket ini — nanti
dibangun di atas tabel `ekstrakurikulers` ini kalau sudah siap lanjut.

## File yang ditambah/diubah
- `database/migrations/2026_08_23_000002_create_ekstrakurikulers_table.php` **(baru)**
  — tabel `ekstrakurikulers`: nama_ekstrakurikuler, pembina_id (nullable,
  FK ke users), keterangan, is_aktif.
- `app/Models/Ekstrakurikuler.php` **(baru)**
- `app/Http/Controllers/EkstrakurikulerController.php` **(baru)** — CRUD
  standar (index/store/update/destroy), akses dibatasi role Kesiswaan & Admin.
- `routes/web.php` — tambah `Route::resource('ekstrakurikuler', ...)`
  dibatasi `role:kesiswaan,admin`.
- `resources/views/ekstrakurikuler/index.blade.php` **(baru)** — halaman
  daftar + form tambah/edit inline (pola sama seperti menu Mata Pelajaran).
- `resources/views/layouts/app.blade.php` — tambah menu sidebar
  "Ekstrakurikuler" (tampil untuk role Kesiswaan & Admin).

## Cara menerapkan (di Laragon)
1. Salin semua file di atas ke lokasi yang sama persis di
   `C:\laragon\www\sim-spenga` (timpa `routes/web.php` dan
   `resources/views/layouts/app.blade.php` yang lama — keduanya cuma
   ditambah beberapa baris, bukan diganti total).
2. Jalankan migrasi:
   ```powershell
   cd C:\laragon\www\sim-spenga
   php artisan migrate
   ```
3. Tidak perlu `npm run build`.
4. Test: login sebagai user role Kesiswaan → menu "Ekstrakurikuler" di
   sidebar → tambah beberapa nama kegiatan, coba juga isi & kosongkan
   pembina, edit, hapus.
