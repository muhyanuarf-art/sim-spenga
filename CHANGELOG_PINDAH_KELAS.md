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
4. **Bug diperbaiki (susulan)**: form isi Jurnal Mengajar + Absensi
   (menu "Isi Absensi" milik guru) untuk TANGGAL LAMPAU juga masih memakai
   kelas siswa SAAT INI (`$kelas->siswas()`), sama seperti bug di poin 1 —
   bedanya di sini belum ada snapshot `absensi_siswas` untuk disandarkan,
   karena memang belum pernah diisi. Kalau guru baru sempat isi absensi
   kelas 7A tanggal 20 Agustus setelah tanggal 24 Agustus (siswa sudah
   pindah ke 7B), siswa itu tidak akan muncul di form-nya sama sekali, jadi
   tidak pernah tercatat kehadirannya untuk sesi itu. Diperbaiki dengan
   merekonstruksi keanggotaan kelas PADA TANGGAL yang diisi dari riwayat
   mutasi (`riwayat_kelas_siswas`) lewat helper baru `KeanggotaanKelas`.

## Fix susulan: File yang diubah/ditambah
- `app/Support/KeanggotaanKelas.php` **(baru)** — helper
  `anggotaPadaTanggal(Kelas $kelas, string $tanggal)`, merekonstruksi siapa
  saja anggota sebuah kelas pada tanggal tertentu dari `riwayat_kelas_siswas`.
- `app/Http/Controllers/MengajarController.php` — `form()` & `store()`
  sekarang memakai `KeanggotaanKelas::anggotaPadaTanggal()` (baik untuk
  menampilkan daftar siswa di form maupun validasi saat submit), bukan
  `$kelas->siswas()` (kelas siswa saat ini).

## Fix susulan #2 (2026-08-23, sore) — 2 bug lagi ditemukan saat testing
Setelah fix susulan di atas dipasang, ditemukan siswa yang dipindah masih
tidak muncul dengan benar di form Isi Absensi. Penyebabnya TERNYATA dua bug
terpisah, bukan satu:

1. **Bug utama (penyebab kedua gejala yang dilaporkan)**: field "Tanggal"
   di form Isi Absensi (`resources/views/absensi/form.blade.php`) adalah
   `<input type="date">` BIASA yang tidak memicu apa pun saat diganti —
   daftar siswa yang tampil TETAP daftar hasil hitungan untuk tanggal saat
   halaman pertama dimuat (default: hari ini), berapa pun tanggal yang
   diketik ulang di kolom itu. Jadi mengganti tanggal ke 22/23 di kolom itu
   tidak pernah benar-benar meminta ulang daftar siswa untuk tanggal
   tersebut ke server. Diperbaiki: kolom tanggal sekarang memuat ulang
   halaman (`onchange` → redirect ke URL yang sama dengan `?tanggal=...`)
   supaya `MengajarController::form()` benar-benar menghitung ulang daftar
   siswa untuk tanggal yang dipilih.
2. **Bug kedua (baru kelihatan setelah bug #1 di atas diperbaiki)**: siswa
   yang ditambahkan lewat "Tambah Siswa" (bukan Import Excel) TIDAK pernah
   punya baris riwayat kelas awal (`riwayat_kelas_siswas` jenis
   `awal_masuk`). Begitu siswa itu dipindah kelas dengan "Pindah Kelas",
   baris riwayat yang tercatat cuma 1 (baris pindahnya sendiri, mulai
   berlaku dari tanggal pindah) — tidak ada baris yang menjelaskan siswa
   itu ada di kelas mana SEBELUM tanggal pindah. Akibatnya
   `KeanggotaanKelas::anggotaPadaTanggal()` salah menyimpulkan siswa itu
   "belum tercatat di kelas manapun" untuk tanggal sebelum pindah, padahal
   ada infonya (di `kelas_asal_id` baris pindah itu sendiri) — cuma belum
   dipakai. Diperbaiki 2 lapis:
   - `KeanggotaanKelas::anggotaPadaTanggal()`: kalau baris riwayat paling
     awal seorang siswa punya `kelas_asal_id` (bukan `awal_masuk`), tanggal
     SEBELUM baris itu dianggap siswa ada di `kelas_asal_id` tsb — menutup
     celah utk data yang SUDAH TERLANJUR begini.
   - `SiswaController::store()`: sekarang otomatis mencatat baris riwayat
     `awal_masuk` saat siswa baru ditambahkan, supaya siswa BARU ke depan
     tidak mengalami celah yang sama lagi.

### File tambahan yang berubah (fix susulan #2)
- `resources/views/absensi/form.blade.php` — field tanggal memuat ulang
  halaman saat diganti.
- `app/Support/KeanggotaanKelas.php` — fallback riwayat pra-mutasi-pertama.
- `app/Http/Controllers/SiswaController.php` — `store()` mencatat riwayat
  awal_masuk; `catatMutasiKelas()` menerima parameter `$jenis` (default
  tetap `pindah_kelas`, jadi pemanggilan lama tidak perlu diubah).

## Fix susulan #3 (2026-08-23, malam) — bug ke-3, ini penyebab utama gejala terakhir
Setelah fix #2 dipasang, siswa yang dipindah TETAP tidak muncul di form kelas
ASAL untuk tanggal sebelum pindah (sudah dites: kolom tanggal & reload
halaman terkonfirmasi jalan normal). Penyebabnya baru ketahuan: bug di
`KeanggotaanKelas::anggotaPadaTanggal()` pada tahap awal, BUKAN di logika
tanggalnya — siswa yang dipindah malah tidak pernah lolos jadi "kandidat"
sama sekali untuk kelas ASAL.

Detail: baris riwayat `pindah_kelas` menyimpan kelas TUJUAN di kolom
`kelas_id` dan kelas ASAL di kolom `kelas_asal_id`. Kandidat siswa yang
diperiksa sebelumnya hanya dicari lewat `RiwayatKelasSiswa::where('kelas_id',
$kelas->id)` — cocok untuk kelas TUJUAN, tapi untuk kelas ASAL ini salah
kolom (harusnya `kelas_asal_id`). Akibatnya siswa yang riwayatnya cuma 1
baris (baris pindah itu sendiri) tidak pernah masuk daftar kandidat saat
kelas yang diperiksa adalah kelas asalnya — jadi dia hilang total dari form
kelas asal, untuk tanggal berapa pun (bahkan sebelum tanggal pindah).

**Fix**: kandidat sekarang dicari lewat `kelas_id` ATAU `kelas_asal_id`.

### File yang berubah (fix susulan #3)
- `app/Support/KeanggotaanKelas.php` — kandidat siswa kelas ASAL kini ikut
  dijaring lewat kolom `kelas_asal_id`, bukan cuma `kelas_id`.

## Cara menerapkan (SEMUA fix susulan, di Laragon)
1. Salin ke `C:\laragon\www\sim-spenga` (timpa yang lama):
   - `app/Support/KeanggotaanKelas.php`
   - `app/Http/Controllers/MengajarController.php`
   - `app/Http/Controllers/SiswaController.php`
   - `resources/views/absensi/form.blade.php`
2. Tidak perlu migrasi baru & tidak perlu `npm run build`.
3. Test manual (di kelas ASAL siswa yang dipindah, tanggal SEBELUM pindah):
   - Buka "Isi Absensi" kelas asal → ganti tanggal ke sebelum tanggal
     pindah → pastikan URL berubah (`?tanggal=...`) → siswa yang dipindah
     HARUS muncul di daftar sekarang.
   - Ganti tanggal ke tanggal pindah / sesudahnya → siswa itu HARUS hilang
     dari kelas asal, dan sebaliknya muncul di form kelas tujuan.

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
