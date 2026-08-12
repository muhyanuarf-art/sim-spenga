# Modul BK — Manajemen Kasus, Pelanggaran, Poin & Pembinaan Siswa

## STATUS
Implementasi Langkah 2-4 selesai (desain database, backend, UI).
Langkah 5 (testing) perlu Anda jalankan di server sungguhan karena
saya tidak punya akses PHP/database untuk eksekusi langsung di sini.

## FILE YANG DIBUAT (33 file)

**Migration (6):** `jenis_pelanggarans`, `kasus_siswas`, `pembinaan_siswas`,
`evaluasi_pembinaans`, `pengurangan_poin_siswas`, `pemanggilan_orangtuas`.

**Model (6):** `JenisPelanggaran`, `KasusSiswa`, `PembinaanSiswa`,
`EvaluasiPembinaan`, `PenguranganPoinSiswa`, `PemanggilanOrangTua`.

**Service (1):** `PoinSiswaService` — **satu-satunya tempat** rumus poin
aktif & rekomendasi tahap dihitung (Bagian 24 spec).

**Support (1):** `BkAccessScope` (trait) — aturan cakupan akses per role,
dipakai bersama semua controller BK.

**Controller (7):** `BkDashboardController`, `BkSiswaController`,
`BkKasusController`, `BkPembinaanController`, `BkPenguranganPoinController`,
`BkPemanggilanController`, `BkJenisPelanggaranController`.

**View (9):** dashboard, monitoring siswa, profil siswa (halaman sentral),
kasus (index+create), pembinaan, pengurangan, pemanggilan, master pelanggaran.

## FILE YANG DIUBAH

| File | Perubahan |
|---|---|
| `app/Models/Siswa.php` | + 4 relasi baru (kasusBk, pembinaanBk, dst) — additive, tidak mengubah relasi lama |
| `routes/web.php` | + grup route `bk.*` |
| `resources/views/layouts/app.blade.php` | + section menu "BK" di sidebar |

## PERUBAHAN DATABASE

6 tabel baru, semua **additive** (tidak mengubah kolom/tabel lama sama
sekali). Detail kolom ada di masing-masing file migration (sudah dikomentari).

## RELASI

```
Siswa hasMany KasusSiswa, PembinaanSiswa, PenguranganPoinSiswa, PemanggilanOrangTua
KasusSiswa belongsTo Siswa, Kelas, JenisPelanggaran, User(pelapor); hasMany PembinaanSiswa, PemanggilanOrangTua
PembinaanSiswa belongsTo Siswa, KasusSiswa, User(petugas); hasMany EvaluasiPembinaan
PenguranganPoinSiswa belongsTo Siswa, User(petugas)
PemanggilanOrangTua belongsTo Siswa, KasusSiswa, User(petugas)
```

## KEPUTUSAN DESAIN PENTING (sesuai konfirmasi Anda)

1. **Siapa boleh lapor kasus**: semua role `guru` (termasuk guru mapel biasa,
   wali kelas, guru_bk), plus admin. Kurikulum/Kepsek tidak melapor (hanya lihat).
2. **Tidak ada workflow approval formal** untuk Tahap 5-7 — Kepala Sekolah
   bisa **melihat semuanya** (dashboard, profil siswa, semua laporan) tapi
   keputusan approval tetap manual di luar sistem (rapat/koordinasi),
   sesuai prinsip "jangan sistem yang putuskan sanksi berat" (Bagian 16 spec).
3. **Role `guru_bk` di-reuse** dari fitur monitoring absensi sebelumnya —
   cakupan akses data BK mengikuti mapping kelas yang sama
   (`guru_bk_kelas`, menu **Mapping Guru BK** yang sudah ada).

## HAL PENTING YANG PERLU DIPAHAMI

### Poin Aktif & Tahap — dihitung LIVE, bukan disimpan
`PoinSiswaService::poinAktif()` selalu menghitung ulang dari SUM transaksi
aktif (`kasus_siswas` dikurangi `pengurangan_poin_siswas`, yang belum
dibatalkan). Tidak ada kolom `total_poin` statis di tabel `siswas` yang
bisa "nyasar"/tidak sinkron.

### Koreksi kesalahan = Batalkan, BUKAN hapus
Kasus & pengurangan poin yang salah input **tidak dihapus dari database**.
Ada tombol "Batalkan" (khusus Guru BK/Admin) yang mengisi kolom
`dibatalkan_at`, `dibatalkan_oleh_id`, `alasan_pembatalan` — baris tetap
ada di database (audit trail), hanya tidak lagi dihitung ke saldo poin.

### Validasi poin sesuai kategori
Form Tambah Kasus **menolak** kombinasi tidak valid, misal "Ringan + 50
poin" — divalidasi server-side via `PoinSiswaService::validasiPoinSesuaiKategori()`,
bukan cuma di JavaScript (jadi tidak bisa diakali lewat DevTools).

### Pengurangan poin tidak bisa melebihi saldo
`BkPenguranganPoinController::store()` menghitung ulang poin aktif
TERKINI di server sebelum menyimpan — kalau jumlah pengurangan melebihi
saldo aktif, transaksi **ditolak** dengan pesan error, bukan diam-diam
memotong angkanya.

### Tahap pembinaan: rekomendasi otomatis, keputusan tetap manual
Sistem menghitung **rekomendasi** tahap 1-5 dari poin aktif (ditampilkan
sebagai hint di form Catat Pembinaan), tapi BK **selalu memilih sendiri**
tahap final (1-7) saat mencatat pembinaan — sistem tidak pernah otomatis
menjatuhkan Tahap 6/7 (skorsing dll).

## CARA PASANG

```bash
php artisan migrate
php artisan route:clear
php artisan view:clear
```

Tidak ada `.env` baru yang perlu diisi untuk modul ini.

## LANGKAH TESTING (sesuai Bagian 31 spec — tolong jalankan manual)

1. Login sebagai **Guru BK** (atau Admin) → menu **Data Pelanggaran (Master)**
   → tambah beberapa jenis pelanggaran contoh (Terlambat/Ringan/5,
   Membolos/Sedang/20, dst).
2. Login sebagai **guru biasa** → menu **BK → Kasus/Pelanggaran** → Catat
   Kasus Baru → pilih siswa, pilih jenis "Terlambat" (otomatis isi kategori
   & poin) → simpan.
   **Cek**: buka profil siswa tsb → Poin Aktif harus **5**.
3. Tambah kasus lagi untuk siswa yang sama, mis. "Membolos" (+20).
   **Cek**: Poin Aktif harus **25**, Rekomendasi tahap harus **Tahap 2**.
4. Login sebagai **Guru BK** → buka profil siswa itu → **Kurangi Poin** →
   isi 10 → simpan.
   **Cek**: Poin Aktif harus **15**.
5. Coba **Kurangi Poin** lagi dengan jumlah **20** (melebihi 15 yang
   tersisa).
   **Cek**: harus **DITOLAK** dengan pesan error, bukan menghasilkan
   poin minus.
6. Cek **riwayat pelanggaran tetap ada** di timeline profil siswa (kedua
   kasus di atas masih tampil, tidak hilang meski sudah ada pengurangan).
7. Cek **tahap pembinaan berubah mengikuti poin aktif** (15 → rekomendasi
   Tahap 1, bukan lagi Tahap 2).
8. Login sebagai **guru mapel biasa** (bukan wali kelas/BK) → coba akses
   halaman **Pengurangan Poin** → harus **403 Forbidden** (tidak
   diizinkan sama sekali, sesuai role middleware).
9. Coba lapor 2 kasus di 2 tab berbeda nyaris bersamaan untuk siswa yang
   sama, lalu kurangi poin di 2 tab juga nyaris bersamaan — pastikan
   tidak ada data setengah jadi (transaksi dibungkus `DB::transaction`).

## CATATAN / KETERBATASAN YANG DISENGAJA (supaya tidak over-engineering di awal)

- **Belum ada export PDF/Excel** khusus modul BK (Bagian 27 spec bilang
  "integrasikan dengan fitur export yang sudah ada" — project sudah
  punya `maatwebsite/excel` untuk **import**, tapi belum ada pola
  **export** yang established untuk saya ikuti; kalau dibutuhkan,
  beri tahu saya, nanti saya tambahkan mengikuti pola serupa).
- **Audit trail** yang dibangun bersifat pragmatis (siapa & kapan
  tercatat di setiap transaksi + mekanisme batalkan-bukan-hapus untuk
  koreksi), BUKAN tabel log generik yang mencatat setiap perubahan
  field satu-per-satu. Ini keputusan sengaja untuk menjaga
  kesederhanaan (prinsip Bagian 33: "kesederhanaan" diprioritaskan
  di atas fitur canggih yang belum tentu dibutuhkan).
- Tombol "Batalkan" pada Pengurangan Poin masih pakai `prompt()`
  browser untuk isi alasan pembatalan (sederhana, berfungsi, tapi
  bukan modal yang sama rapinya dengan form lain) — bisa dirapikan
  nanti kalau diperlukan.
- Import Excel untuk master Data Pelanggaran belum ada (baru CRUD
  manual) — bisa ditambahkan menyusul pola `SiswaImport` yang sudah ada.

## TINDAKAN BERIKUTNYA

Setelah Anda testing dan konfirmasi semua skenario di atas berjalan
sesuai harapan, beri tahu saya kalau ada yang perlu disesuaikan
(misal: rentang poin per kategori beda dengan kebijakan sekolah, atau
mau tambah fitur export/import).
