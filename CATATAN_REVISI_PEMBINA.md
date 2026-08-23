# Revisi: Ekstrakurikuler bisa punya BANYAK pembina (termasuk dari luar sekolah)

## Apa yang berubah
Sebelumnya 1 kegiatan ekstrakurikuler cuma bisa punya 1 pembina, dan
harus user terdaftar di sistem. Sekarang:
- 1 kegiatan boleh punya **berapa pun pembina** (dicentang di form, bukan
  dropdown lagi).
- Pembina boleh **dari luar sekolah** (tidak punya akun di sistem) — cukup
  ketik namanya di kotak terpisah, 1 nama per baris, opsional dengan
  kontak (format: `Nama, No HP`).

## Kenapa perlu migrasi lagi
Kolom `pembina_id` di tabel `ekstrakurikulers` (dari paket sebelumnya)
dihapus, diganti tabel baru `ekstrakurikuler_pembinas` — 1 baris per
pembina, `user_id` diisi kalau pembina staf sekolah, atau `nama_eksternal`
(+`kontak_eksternal` opsional) kalau dari luar. Kalau sebelumnya sempat ada
data yang keburu diisi lewat form lama, migrasi ini otomatis memindahkannya
ke tabel baru dulu sebelum kolom lama dihapus — tidak ada data hilang.

## File yang diubah/ditambah
- `database/migrations/2026_08_23_000003_create_ekstrakurikuler_pembinas_table.php` **(baru)**
- `app/Models/EkstrakurikulerPembina.php` **(baru)**
- `app/Models/Ekstrakurikuler.php` — relasi `pembina()` (1 pembina) diganti
  `pembinas()` (banyak) + helper `daftarNamaPembina()` untuk tampilan.
- `app/Http/Controllers/EkstrakurikulerController.php` — `store()`/`update()`
  sekarang menerima `pembina_internal[]` (checkbox, banyak) dan
  `pembina_eksternal` (textarea multi-baris), ganti seluruh baris pembina
  kegiatan tiap disimpan (hapus lalu buat ulang).
- `resources/views/ekstrakurikuler/index.blade.php` — form checkbox
  multi-pilih untuk pembina internal + textarea untuk pembina luar sekolah,
  baik di form tambah maupun form edit inline; kolom "Pembina" di tabel
  menampilkan semua nama digabung (pembina luar sekolah diberi label
  "(luar sekolah)").
- (File `database/migrations/2026_08_23_000002_create_ekstrakurikulers_table.php`
  ikut disertakan di paket ini untuk referensi/urutan migrasi — TIDAK
  berubah isinya dari paket sebelumnya, cukup pastikan sudah pernah
  dijalankan sebelum migrasi baru di atas.)

## Cara menerapkan (di Laragon)
1. Salin ke `C:\laragon\www\sim-spenga` (timpa yang lama):
   - `database/migrations/2026_08_23_000003_create_ekstrakurikuler_pembinas_table.php` (baru)
   - `app/Models/Ekstrakurikuler.php`
   - `app/Models/EkstrakurikulerPembina.php` (baru)
   - `app/Http/Controllers/EkstrakurikulerController.php`
   - `resources/views/ekstrakurikuler/index.blade.php`
2. Jalankan migrasi:
   ```powershell
   cd C:\laragon\www\sim-spenga
   php artisan migrate
   ```
3. Tidak perlu `npm run build`.
4. Test: tambah 1 kegiatan, centang 2+ pembina internal SEKALIGUS isi
   1-2 baris pembina luar sekolah (salah satu dengan kontak, salah satu
   tanpa) → simpan → pastikan kolom "Pembina" di tabel menampilkan semua
   nama itu, yang luar sekolah ada label "(luar sekolah)". Coba juga Edit
   untuk pastikan centang & isian textarea muncul sesuai data tersimpan.
