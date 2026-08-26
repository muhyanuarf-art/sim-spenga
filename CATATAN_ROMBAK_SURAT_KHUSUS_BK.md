# ROMBAK TOTAL: Modul Surat sekarang KHUSUS Keperluan BK

Sesuai instruksi terlampir (`Buat_surat_hanya_untuk_keperluan_BK_saja.docx`
+ 3 gambar contoh format).

## Perubahan besar

### 1. Hak akses
- **Guru BK**: satu-satunya yang bisa **buat/edit/hapus** surat.
- **Kesiswaan, Kurikulum, Kepala Sekolah**: **baca saja** — bisa buka
  daftar surat & lihat/cetak isinya, TIDAK ada tombol buat/edit/hapus.
- **TU**: kelola **Master Jenis Surat** saja (nama, kode, status aktif,
  dan template untuk Surat Panggilan) — TIDAK melihat arsip surat
  individual siswa lagi.
- **Admin**: tetap bisa semuanya (bypass otomatis, konsisten dengan pola
  role di seluruh aplikasi ini).

### 2. Nomor surat — format baru
```
422/{nomor urut}/BK/{bulan romawi}/{tahun}
```
Contoh: `422/15/BK/VIII/2026`
- `422` dan `BK` tetap/otomatis.
- Bulan romawi & tahun otomatis dari tanggal surat.
- **Nomor urut WAJIB diisi manual** oleh Guru BK (bukan lagi
  auto-generate) — sesuai buku agenda surat fisik yang sudah dipakai.

### 3. 4 Jenis Surat BK
| Jenis | Bentuk |
|---|---|
| Surat Izin Meninggalkan Pelajaran | Form baku (field tetap) |
| Surat Keterangan Terlambat | Form baku (field tetap) |
| Surat Pernyataan Pelanggaran Siswa | Form baku (field tetap) |
| Surat Panggilan Orang Tua/Wali | Template bebas (seperti sebelumnya) |

3 jenis pertama punya field TETAP sesuai contoh kertas BK yang sudah ada
(Nama, Kelas, Alamat, dll — bukan lagi kotak teks bebas) — datanya
disimpan terstruktur, dicetak persis meniru format kertas aslinya
(termasuk tanda tangan "Mengetahui / Guru Mata Pelajaran" di kiri kosong
untuk ditandatangani manual, dan "Koordinator/Staf BK" di kanan otomatis
terisi nama Guru BK yang membuat surat).

**Surat Panggilan Orang Tua/Wali** tetap pakai cara lama (pilih/cari
siswa, isi digabung otomatis dari template) — cuma tanda tangannya
**ditambah jadi 2**: Kepala Sekolah (kiri, otomatis dari Pengaturan
Sekolah) + Guru BK (kanan, otomatis dari akun yang membuat).

### 4. Fitur yang DIHILANGKAN dari alur ini
Tidak ada di spesifikasi baru, jadi dibuang dari menu (tabel di database
TETAP ADA, tidak dihapus — kalau ada data lama, aman):
- Disposisi (kirim/terima/tindak lanjut).
- Lampiran/upload file per surat.
- Riwayat Aktivitas.
- Dashboard "Surat Masuk" & statistik terkait disposisi.
- Jenis surat non-BK yang sempat dibuat sebelumnya (Surat Keterangan
  Aktif, Pindah, Berkelakuan Baik, dst.) — **dinonaktifkan**, bukan
  dihapus, lewat migrasi.

## File yang diubah/ditambah
**Migrasi (baru):** `2026_08_26_000004_rombak_surat_khusus_bk.php` —
tambah kolom `tipe_formulir` (jenis_surats) & `data_formulir` json
(surats), nonaktifkan jenis lama, buat 3 jenis surat BK baru.

**Baru:** `app/Support/NomorSuratBk.php` (format nomor baru).

**Diubah total:** `SuratController`, `SuratDashboardController`,
`BkPemanggilanController` (ikut pindah ke `NomorSuratBk` + nomor urut
manual), model `Surat` & `JenisSurat`, semua view di `resources/views/surat/`,
`resources/views/bk/pemanggilan/create.blade.php`, sidebar
(`layouts/app.blade.php`), `routes/web.php`.

## Cara menerapkan (di Laragon) — PENTING
1. Salin SEMUA file di atas ke lokasi yang sama persis di
   `C:\laragon\www\sim-spenga` (timpa `routes/web.php` dan
   `resources/views/layouts/app.blade.php`, cuma bagian terkait Surat
   yang berubah).
2. Jalankan migrasi:
   ```powershell
   cd C:\laragon\www\sim-spenga
   php artisan migrate
   ```
3. Tidak perlu `npm run build` (tidak ada class Tailwind baru).
4. Test yang WAJIB dicoba:
   - Login **Guru BK** → menu "Manajemen Surat" → coba buat masing-masing
     4 jenis surat → cek nomor surat, isi cetakan, dan tanda tangan
     sesuai contoh kertas.
   - Login **Kesiswaan/Kurikulum/Kepala Sekolah** → cek menu "Surat (BK)"
     muncul, bisa buka & cetak surat, TAPI **tidak ada** tombol Buat/Edit/Hapus.
   - Login **TU** → cek menu "Jenis Surat" — bisa ubah nama/status aktif,
     TAPI tidak bisa lihat daftar surat individual siswa.
   - Coba akses `/surat/create` langsung via URL sebagai Kesiswaan →
     harus ditolak (403).
