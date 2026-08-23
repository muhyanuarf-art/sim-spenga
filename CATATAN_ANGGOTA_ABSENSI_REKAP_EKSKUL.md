# Fitur baru: Anggota, Absensi, dan Rekap Ekstrakurikuler

## Apa yang dibuat
Melengkapi fitur Ekstrakurikuler yang sebelumnya baru sebatas master data
kegiatan + pembina. Sekarang lengkap:

1. **Anggota** — Kesiswaan/Admin assign siswa ke kegiatan lewat tombol
   "Anggota" di menu Ekstrakurikuler. Cari siswa (nama/NIS), tambahkan,
   atau keluarkan. Siswa lintas kelas/angkatan bisa 1 kegiatan yang sama,
   dan 1 siswa boleh ikut banyak kegiatan.
2. **Absensi** — sesuai aturan yang Anda tetapkan:
   - **Yang mengisi**: hanya pembina dari SEKOLAH (guru/guru_bk yang
     terdaftar sebagai pembina kegiatan itu), atau Kesiswaan/Admin
     (mewakili). Pembina luar sekolah tidak pernah bisa mengisi sendiri —
     memang tidak punya akun.
   - **Yang diabsen**: SISWA anggota kegiatan **dan** SEMUA pembina
     (sekolah maupun luar sekolah) — jadi kehadiran pembina juga tercatat.
   - Guru/Guru BK akses lewat menu sidebar baru "Absensi Ekskul" (hanya
     menampilkan kegiatan yang mereka bina). Kesiswaan/Admin akses lewat
     tombol "Absensi" di menu Ekstrakurikuler (bisa kegiatan apa saja).
   - Kolom tanggal di form ini SUDAH memakai pola reload-saat-ganti-tanggal
     (pelajaran dari perbaikan Absensi Guru Mapel sebelumnya), jadi aman
     dipakai untuk isi absensi tanggal lampau juga.
3. **Rekap** — laporan bulanan per kegiatan (siswa x tanggal 1-31, format
   S/I/A sama seperti Rekap Absensi Kelas), bisa dicetak/export PDF. Bisa
   dilihat Kesiswaan/Admin (semua kegiatan) atau pembina internal kegiatan
   itu sendiri.

## File yang ditambah/diubah
**Migrasi (baru):**
- `2026_08_23_000004_create_ekstrakurikuler_siswas_table.php` — anggota
  (pivot siswa↔kegiatan).
- `2026_08_23_000005_create_absensi_ekskuls_table.php` — 2 tabel:
  `absensi_ekskuls` (header sesi per tanggal) dan `absensi_ekskul_pesertas`
  (1 baris per peserta — siswa ATAU pembina, lihat komentar di migrasinya).

**Model (baru):** `EkstrakurikulerSiswa`, `AbsensiEkskul`, `AbsensiEkskulPeserta`.
**Model (diubah):** `Ekstrakurikuler` — tambah relasi `anggotas()`,
`absensis()`, dan helper `isPembinaInternal()` untuk otorisasi.

**Controller (baru):**
- `EkstrakurikulerAnggotaController` — CRUD anggota (Kesiswaan/Admin).
- `EkskulAbsensiController` — pilih kegiatan, form isi, simpan absensi.
- `EkskulRekapController` — rekap bulanan per kegiatan.

**Routes** (`routes/web.php`) — ditambah, tidak ada yang dihapus/diubah dari yang lama.

**View (baru):** `ekstrakurikuler/anggota.blade.php`,
`ekstrakurikuler/absensi-pilih.blade.php`, `ekstrakurikuler/absensi-form.blade.php`,
`ekstrakurikuler/rekap-bulanan.blade.php`.
**View (diubah):** `ekstrakurikuler/index.blade.php` — tombol Anggota/Absensi/Rekap
per baris kegiatan.

**Sidebar** (`resources/views/layouts/app.blade.php`) — tambah menu
"Absensi Ekskul" untuk role Guru & Guru BK.

## Cara menerapkan (di Laragon)
1. Salin SEMUA file di atas ke lokasi yang sama persis di
   `C:\laragon\www\sim-spenga` (timpa `routes/web.php` dan
   `resources/views/layouts/app.blade.php` — keduanya cuma ditambah
   beberapa baris/blok, bukan diganti total; juga timpa
   `resources/views/ekstrakurikuler/index.blade.php`, `app/Models/Ekstrakurikuler.php`).
2. Jalankan migrasi:
   ```powershell
   cd C:\laragon\www\sim-spenga
   php artisan migrate
   ```
3. Tidak perlu `npm run build`.
4. Test yang disarankan:
   - Kesiswaan: buka Ekstrakurikuler → kegiatan "Pramuka" → tombol
     "Anggota" → cari & tambahkan 2-3 siswa.
   - Login sebagai salah satu guru yang jadi pembina kegiatan itu → menu
     sidebar "Absensi Ekskul" → pastikan HANYA kegiatan yang dia bina yang
     muncul → isi absensi (siswa + pembina) untuk hari ini → simpan.
   - Ganti tanggal ke kemarin (pastikan URL berubah `?tanggal=...`) → isi
     lagi untuk tanggal itu → simpan.
   - Login sebagai guru LAIN yang BUKAN pembina kegiatan itu → coba akses
     URL absensi kegiatan itu langsung → harus ditolak (403).
   - Kesiswaan: buka tombol "Rekap" kegiatan itu → pastikan data yang
     baru diisi muncul di kolom tanggal yang benar, coba cetak.
