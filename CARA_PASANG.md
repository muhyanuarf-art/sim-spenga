# Perbaikan: Status "Terisi" di Dashboard Guru

## Bug-nya

Kartu "Jadwal Mengajar Hari Ini" di dashboard guru tidak pernah
menampilkan badge **"Terisi"** meskipun jurnal & absensinya sudah
diisi, karena `DashboardController` tidak menghitung status
`sudah_diisi` sama sekali — beda dengan halaman "Absensi & Jurnal
Mengajar" yang sudah menghitungnya dengan benar.

## Perbaikan

1. Logic "cek sudah diisi atau belum" sekarang dipindah ke 1 tempat:
   `SesiMengajarGrouper::tandaiSudahDiisi()` — dipakai bersama oleh
   `MengajarController` (halaman Absensi & Jurnal) DAN
   `DashboardController` (dashboard guru). Jadi hasilnya **dijamin
   sama persis**, bukan ditulis 2x secara terpisah yang berisiko
   beda di kemudian hari.
2. Tampilan kartu di dashboard guru sekarang pakai style yang sama
   persis dengan halaman Absensi & Jurnal saat sudah terisi: border
   & background hijau (`border-emerald-200 bg-emerald-50/60`) +
   badge hijau **"Terisi"**.
3. Untuk sesi yang BELUM terisi, kartu tetap pakai warna per-mapel
   (fitur "dashboard lebih berwarna" sebelumnya) — tidak berubah.

## File yang diubah

| File | Keterangan |
|---|---|
| `app/Support/SesiMengajarGrouper.php` | + method `tandaiSudahDiisi()` (sumber logic tunggal) |
| `app/Http/Controllers/DashboardController.php` | Pakai `tandaiSudahDiisi()` untuk `$jadwalHariIni` |
| `app/Http/Controllers/MengajarController.php` | Disederhanakan, pakai `tandaiSudahDiisi()` juga (perilaku tidak berubah, cuma tidak duplikat kode lagi) |
| `resources/views/dashboard/guru.blade.php` | Kartu jadwal: badge & warna "Terisi" sama persis dgn halaman Absensi & Jurnal |

## Cara pasang

1. Salin 4 file di atas ke project Anda (timpa yang lama).
2. Tidak perlu migration.
3. Clear cache view:
   ```bash
   php artisan view:clear
   ```
4. Test: isi absensi & jurnal untuk 1 sesi mengajar hari ini, lalu
   buka dashboard guru — kartu sesi itu harus langsung berubah jadi
   hijau dengan badge "Terisi", sama seperti tampilannya di menu
   "Absensi & Jurnal Mengajar".
