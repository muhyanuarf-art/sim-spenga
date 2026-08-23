# Paket: Perbaikan Laporan + Fitur "Pindah Kelas"

## Ringkasan
1. **Bug diperbaiki**: 2 laporan (Rekap Absensi Bulanan Wali Kelas & Absensi
   Guru Tiap Mapel) sebelumnya membangun daftar siswa dari kelas SISWA SAAT
   INI, bukan dari kelas yang tercatat di baris absensi bulan itu (snapshot).
   Akibatnya, siswa yang pindah kelas "hilang" dari laporan bulan-bulan
   sebelum ia pindah, padahal datanya aman di database. Sudah diperbaiki:
   daftar siswa sekarang gabungan dari (a) siapa saja yang PERNAH tercatat
   di kelas itu pada bulan yang dilihat (dari `absensi_siswas.kelas_id`)
   dan (b) siswa yang SEKARANG terdaftar di kelas itu.
2. **Fitur baru**: menu aksi "Pindah Kelas" di tiap baris siswa (halaman
   Data Siswa), untuk mencatat siswa yang pindah kelas DI TENGAH tahun
   ajaran berjalan (mis. Juli-Agustus di 7A, September pindah ke 7B —
   masih tahun ajaran yang sama).
3. Sebagai jaring pengaman, form **Edit Data Siswa** yang sudah ada (yang
   juga bisa mengubah kelas) sekarang OTOMATIS mencatat riwayat mutasi juga
   kalau kelas_id berubah lewat situ — supaya Riwayat Kelas tetap akurat
   apa pun jalur yang dipakai admin/kurikulum.

## Kenapa perlu migrasi
Tabel `riwayat_kelas_siswas` (dipakai untuk halaman "Riwayat Kelas" — admin
& Portal Orang Tua) sebelumnya dikunci `unique(siswa_id, tahun_ajaran_id)`,
karena dirancang untuk 1x kenaikan kelas per tahun ajaran. Migrasi baru
menghapus kunci itu supaya siswa bisa punya BEBERAPA baris riwayat dalam
tahun ajaran yang sama (kenaikan kelas dari import Excel + pindah kelas
tengah tahun sekaligus), lalu menambah kolom `jenis` (awal_masuk /
kenaikan_kelas / pindah_kelas) dan `tanggal_mutasi` (tanggal efektif
pindah, untuk urutan tampil). Data lama di-backfill otomatis, tidak hilang.

## File yang diubah/ditambah
- `database/migrations/2026_08_23_000001_add_pindah_kelas_support_to_riwayat_kelas_siswas_table.php` **(baru)**
- `app/Models/RiwayatKelasSiswa.php` — tambah kolom `jenis`, `tanggal_mutasi`, konstanta jenis, `labelJenis()`
- `app/Models/Siswa.php` — `riwayatKelas()` diurutkan berdasarkan `tanggal_mutasi`
- `app/Imports/SiswaImport.php` — riwayat kenaikan kelas dari import di-scope juga dengan `jenis` (supaya tidak bentrok dengan baris pindah kelas tengah tahun)
- `app/Http/Controllers/SiswaController.php` — method baru `pindahKelas()` + helper `catatMutasiKelas()`, dipanggil juga dari `update()`
- `app/Http/Controllers/WaliKelasController.php` — **perbaikan bug** daftar siswa di `absensiBulanan()`
- `app/Http/Controllers/LaporanGuruController.php` — **perbaikan bug** daftar siswa di `absensiMapel()`
- `routes/web.php` — route baru `POST siswa/{siswa}/pindah-kelas`
- `resources/views/siswa/index.blade.php` — tombol & form inline "Pindah Kelas"
- `resources/views/riwayat-kelas/show.blade.php` — badge jenis riwayat + tanggal efektif
- `resources/views/orangtua/dashboard.blade.php` — tanggal efektif di riwayat kelas (Portal Ortu)

## Cara menerapkan (di Laragon)
1. Salin semua file di atas ke lokasi yang sama persis di
   `C:\laragon\www\sim-spenga` (timpa file lama).
2. Jalankan migrasi:
   ```powershell
   cd C:\laragon\www\sim-spenga
   php artisan migrate
   ```
3. Tidak perlu `npm run build` (tidak ada perubahan asset JS/CSS baru,
   hanya pakai class Tailwind yang sudah ada).
4. Test manual yang disarankan:
   - Buka Data Siswa → klik "Pindah Kelas" pada 1 siswa → pilih kelas
     tujuan berbeda → submit → cek kelas siswa berubah & muncul di
     "Riwayat Kelas" dengan badge "Pindah Kelas".
   - Buka Rekap Absensi Bulanan (Wali Kelas) untuk kelas ASAL, pilih bulan
     SEBELUM siswa pindah → pastikan siswa tsb masih muncul dengan data
     absensinya.
   - Buka laporan yang sama untuk kelas TUJUAN, bulan SEBELUM pindah →
     pastikan siswa tsb TIDAK muncul di situ (karena memang belum pernah
     tercatat di kelas itu pada bulan tsb).
   - Ulangi untuk menu "Absensi Guru Tiap Mapel" (Laporan).

## Catatan
- Menu "Pindah Kelas" hanya menawarkan kelas tujuan dari
  `Kelas::aktif()` (kelas tahun ajaran yang sedang aktif) — konsisten
  dengan aturan lain di sistem (siswa baru, edit siswa, dll).
- "Jurnal Mengajar Guru Tiap Mapel" (bukan yang absensi) tidak disentuh
  sama sekali karena memang sudah aman (per sesi mengajar, bukan per
  siswa).
