# STEP 7 — Audit Final: Perbaikan Kritis

## Isi paket (2 file)

```
database/migrations/2026_08_21_000001_restrict_delete_on_periode_relasi.php  (BARU)
app/Http/Controllers/SiswaController.php   (TIMPA)
```

## PENTING — WAJIB BACKUP DATABASE SEBELUM MIGRATE

Migrasi ini mengubah 12 tabel (mengganti `cascadeOnDelete()` menjadi
`restrictOnDelete()` pada kolom `kelas_id`/`tahun_ajaran_id`/`siswa_id`).
Migrasi TIDAK mengubah/menghapus data apa pun — hanya mengganti ATURAN
yang berlaku SAAT NANTI ada percobaan hapus. Tapi karena ini menyentuh
constraint di 12 tabel sekaligus, tetap backup dulu sebelum menjalankan
`php artisan migrate`.

## Temuan Bug (paling serius dari seluruh audit STEP 1-7)

Sebelum perbaikan ini, kolom `kelas_id` / `tahun_ajaran_id` / `siswa_id`
pada tabel-tabel transaksi/histori (siswas, kelas, guru_mengajar_kelas,
jadwal_pelajarans, jurnal_mengajars, absensi_siswas, guru_bk_kelas,
kasus_siswas, pembinaan_siswas, pengurangan_poin_siswas,
pemanggilan_orangtuas, riwayat_kelas_siswas) masih `cascadeOnDelete()`.

Artinya: menghapus **satu baris Tahun Ajaran yang belum terkunci**
(fitur yang sudah ada sejak STEP 1 — hanya menolak yang SUDAH terkunci,
tidak mengecek data terkait) akan **mencascade** menghapus:

```
Tahun Ajaran dihapus
  → semua Kelas tahun itu ikut terhapus
    → semua Siswa di kelas itu ikut terhapus PERMANEN
      → semua Riwayat Kelas, Kasus BK, Pembinaan, Pengurangan Poin,
        Pemanggilan Ortu, Absensi, Akun Orang Tua milik siswa itu
        ikut terhapus PERMANEN
```

Ini bertentangan langsung dengan prinsip utama yang dipegang sejak
STEP 1: **"histori tidak boleh hilang"**. Satu klik hapus tahun ajaran
yang kelihatannya aman bisa memusnahkan data siswa & BK tanpa
peringatan sama sekali.

**Ditemukan juga**: `SiswaController::destroy()` memanggil
`$siswa->delete()` langsung **tanpa perlindungan apa pun** — beda
dengan `KelasController`/`TahunAjaranController` yang sejak awal
sudah memakai helper `hapusAtauGagalDenganPesan()` (menangkap error
"data masih dipakai" dan menampilkannya dengan ramah). Karena FK-nya
cascade, tombol hapus siswa yang punya histori akan **tetap "berhasil"**
padahal diam-diam menghapus seluruh riwayatnya.

## Perbaikan

1. Migrasi baru mengganti `cascadeOnDelete()` → `restrictOnDelete()`
   pada kolom-kolom di atas. Efeknya: percobaan hapus Kelas/Tahun
   Ajaran/Siswa yang **masih punya data terkait** sekarang **ditolak**
   oleh database, bukan diam-diam mencascade.
2. `SiswaController::destroy()` sekarang memakai helper
   `hapusAtauGagalDenganPesan()` yang sama seperti Kelas & Tahun
   Ajaran — menangkap penolakan itu dan menampilkan pesan ramah:
   *"Siswa ini tidak dapat dihapus karena sudah memiliki data terkait
   ... Gunakan toggle nonaktifkan di form edit untuk siswa yang
   lulus/keluar."*

Tombol Hapus di Kelas/Tahun Ajaran/Siswa **tetap ada** dan tetap
berfungsi normal untuk data yang benar-benar belum punya histori apa
pun (mis. kelas baru yang salah ketik, siswa yang salah input dan
belum pernah diabsen).

## Cara pasang

1. **Backup database.**
2. Timpa `app/Http/Controllers/SiswaController.php`.
3. Tambahkan file migrasi baru.
4. `php artisan migrate`.
5. Coba hapus Tahun Ajaran/Kelas/Siswa yang punya data → harus
   muncul pesan ramah "masih memiliki data terkait", BUKAN
   ter-hapus diam-diam.

## Testing yang disarankan

1. Buat Tahun Ajaran baru (belum terkunci), buat 1 kelas & 1 siswa di
   dalamnya, lalu coba hapus Tahun Ajaran itu → harus DITOLAK dengan
   pesan jelas.
2. Coba hapus Kelas yang masih ada siswanya → harus DITOLAK.
3. Coba hapus Siswa yang sudah pernah diabsen/punya kasus BK → harus
   DITOLAK, bukan "berhasil" tapi riwayatnya hilang.
4. Buat Tahun Ajaran/Kelas/Siswa baru yang benar-benar kosong (belum
   ada apa-apa) → hapus harus tetap BERHASIL seperti biasa.
