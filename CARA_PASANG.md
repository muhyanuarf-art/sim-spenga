# Fitur Baru: Role & Menu Guru BK

## Apa yang dibuat

Role baru **Guru BK**, dengan menu monitoring absensi yang otomatis
menampilkan **hanya kelas-kelas yang di-mapping-kan** kepadanya oleh
Kurikulum/Admin (bisa lebih dari 1 kelas per Guru BK) — persis
seperti yang diminta.

## Alur kerjanya

1. **Admin** membuat akun pengguna baru dengan role **"Guru BK"** di
   menu Kelola Pengguna.
2. **Kurikulum/Admin** membuka menu baru **"Mapping Guru BK"**, lalu
   menentukan Guru BK tsb bertanggung jawab memantau kelas apa saja
   (mis. Pak Budi bertanggung jawab atas kelas 7A, 7B, 8C).
3. **Guru BK** login → dashboard otomatis menampilkan ringkasan
   semua kelas mapping-nya + siswa Alfa hari ini lintas kelas
   tersebut, dan bisa buka Rekap Absensi Bulanan / Jurnal Kelas /
   Status WhatsApp Ortu — semuanya otomatis dibatasi hanya ke
   kelas-kelas mapping-nya (tidak bisa lihat kelas lain, termasuk
   kalau URL-nya diakali langsung).

## Menu yang didapat Guru BK

| Menu | Cakupan |
|---|---|
| **Dashboard** | Ringkasan semua kelas mapping-nya, badge "🚩 X Alfa" per kelas, daftar siswa Alfa hari ini lintas kelas |
| **Rekap Absensi Bulanan** | Bisa pilih di antara kelas-kelas mapping-nya (dropdown otomatis dibatasi) |
| **Jurnal Mengajar Kelas** | Sama, dibatasi ke kelas mapping-nya |
| **Status WhatsApp Ortu** | Histori notifikasi Alfa untuk siswa di kelas-kelas mapping-nya, dengan filter bulan |

Guru BK **tidak** mendapat akses ke "Absensi & Jurnal Mengajar" (itu
khusus guru yang benar-benar mengajar mapel & punya jadwal jam
pelajaran) maupun "Laporan Jurnal/Absensi Tiap Mapel" (levelnya per
mapel, bukan ranah BK).

## File yang ditambah/diubah

| File | Keterangan |
|---|---|
| `database/migrations/..._add_guru_bk_role_to_users_table.php` | Tambah `guru_bk` ke enum role |
| `database/migrations/..._create_guru_bk_kelas_table.php` | Tabel mapping Guru BK ↔ Kelas |
| `app/Models/GuruBkKelas.php` | Model baru |
| `app/Models/User.php` | + `isGuruBk()`, `bkKelas()`, `kelasBk()`, label role |
| `app/Http/Controllers/UserController.php` | Validasi role terima `guru_bk` |
| `app/Http/Controllers/GuruBkController.php` | Controller baru — CRUD mapping (Kurikulum/Admin) |
| `app/Http/Controllers/WaliKelasController.php` | Diperluas: mendukung multi-kelas untuk Guru BK (sebelumnya cuma 1 kelas untuk Wali Kelas) |
| `app/Http/Controllers/NotifikasiWhatsappController.php` | Diperluas: cakupan data untuk Guru BK |
| `app/Http/Controllers/DashboardController.php` | + dashboard khusus Guru BK |
| `routes/web.php` | + route mapping, + `guru_bk` di middleware terkait |
| `resources/views/users/index.blade.php` | + opsi role "Guru BK" |
| `resources/views/kurikulum/guru-bk/index.blade.php` | View baru — halaman mapping |
| `resources/views/walikelas/absensi-bulanan.blade.php` & `jurnal-kelas.blade.php` | Dropdown kelas ikut muncul untuk Guru BK |
| `resources/views/notifikasi-wa/index.blade.php` | Info kelas mapping untuk Guru BK |
| `resources/views/dashboard/guru-bk.blade.php` | View baru — dashboard Guru BK |
| `resources/views/layouts/app.blade.php` | Menu sidebar disesuaikan (section "Wali Kelas" di-relabel "Monitoring Kelas" karena sekarang dipakai 2 peran) |

## Cara pasang

1. Salin semua file di atas ke project Anda (timpa yang lama).
2. Jalankan migration:
   ```bash
   php artisan migrate
   ```
3. Clear cache:
   ```bash
   php artisan route:clear
   php artisan view:clear
   php artisan config:clear
   ```
4. Test:
   - Buat 1 akun baru role "Guru BK" di Kelola Pengguna.
   - Login sebagai Kurikulum/Admin → buka **Mapping Guru BK** →
     mapping akun tsb ke 2-3 kelas berbeda.
   - Login sebagai akun Guru BK tsb → dashboard harus menampilkan
     semua kelas yang di-mapping tadi.
   - Buka Rekap Absensi Bulanan → dropdown kelas harus HANYA
     menampilkan kelas mapping-nya (bukan semua kelas sekolah).
   - Coba akses kelas LAIN (bukan mapping-nya) lewat URL langsung
     (mis. ganti angka ID di URL) → harus muncul error 403 "Anda
     tidak memiliki akses ke kelas ini."
   - Cek menu Status WhatsApp Ortu → data juga otomatis terbatas ke
     kelas mapping-nya.

## Catatan

- Mapping Guru BK bersifat **per Tahun Ajaran** (sama seperti mapping
  guru mapel) — kalau tahun ajaran baru diaktifkan nanti, mapping
  perlu diinput ulang untuk tahun ajaran itu.
- Belum ada fitur import Excel untuk mapping Guru BK (baru manual
  1-per-1 lewat form). Kalau nanti jumlah Guru BK & kelasnya banyak
  dan perlu import massal, saya bisa tambahkan mengikuti pola yang
  sama seperti import Mapping Guru Mengajar.
