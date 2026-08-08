# Fitur: Daftar Siswa Alfa Hari Ini di Semua Dashboard

## Aturan datanya

Daftar ini mengambil dari **Absensi Kelas** — yaitu status siswa dari
guru mapel dengan **jam pelajaran paling akhir** yang sudah mengisi
absensi hari ini (aturan yang sama seperti perbaikan Rekap Wali
Kelas sebelumnya). Dihitung ulang otomatis setiap dashboard dibuka
(live), bukan data tersimpan statis.

## Tampil di mana saja

| Dashboard | Cakupan |
|---|---|
| **Admin / Kepala Sekolah** | Seluruh sekolah, semua kelas |
| **Kurikulum** | Seluruh sekolah, semua kelas |
| **Guru** | HANYA tampil kalau guru tsb adalah **Wali Kelas**, dan hanya untuk kelas walinya sendiri. Guru mapel biasa (bukan wali kelas) tidak melihat widget ini di dashboard-nya. |

## File yang diubah

| File | Keterangan |
|---|---|
| `app/Models/AbsensiSiswa.php` | + method statis `siswaAlfaHariIni(?$kelasId)` |
| `app/Http/Controllers/DashboardController.php` | Semua 3 varian dashboard sekarang kirim variabel `$siswaAlfaHariIni`. Sekalian **rekap "Hadir/Sakit/Izin/Alfa" di dashboard Admin diperbaiki** juga (sebelumnya bisa dobel-hitung kalau 1 siswa diabsen banyak mapel di hari yang sama — sekarang konsisten pakai status final per hari) |
| `resources/views/dashboard/admin.blade.php` | + card "🚩 Siswa Alfa Hari Ini" |
| `resources/views/dashboard/kurikulum.blade.php` | + card "🚩 Siswa Alfa Hari Ini" |
| `resources/views/dashboard/guru.blade.php` | + card "🚩 Siswa Alfa Hari Ini" (khusus Wali Kelas, di dalam banner wali kelas) |

## Cara pasang

1. Salin 5 file di atas ke project Anda (timpa yang lama).
2. Tidak perlu migration, tidak ada perubahan struktur database.
3. Clear cache view:
   ```bash
   php artisan view:clear
   ```
4. Test: buat 1 siswa Alfa hari ini di 1 mapel (guru mapel paling
   akhir jam-nya pada hari itu), lalu cek dashboard Admin, Kurikulum,
   dan dashboard guru yang jadi Wali Kelas kelas tsb — nama siswa
   itu harus muncul di ketiganya, dengan info mapel & jam yang sama.
