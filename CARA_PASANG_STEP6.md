# STEP 6 — Guru Mengajar & Jadwal Antar Tahun Ajaran

Lanjutan dari STEP 1-5. **Tidak ada migrasi baru** di STEP 6 ini —
murni perbaikan logic & UX di atas struktur yang sudah ada.

## Isi paket (8 file, semua TIMPA)

```
app/Http/Controllers/TahunAjaranController.php
app/Http/Controllers/JadwalController.php
app/Http/Controllers/GuruMengajarController.php
routes/web.php   ⚠️ lihat catatan di bawah
resources/views/tahun-ajaran/index.blade.php
resources/views/tahun-ajaran/preview-duplikasi.blade.php   (BARU)
resources/views/jadwal/index.blade.php
resources/views/kurikulum/guru-mengajar/index.blade.php
```

## PENTING — bug yang diperbaiki

Audit STEP 6 menemukan **bug nyata** peninggalan STEP 5: fitur "Salin
Mapping Guru/Jadwal" (dibuat sebelum STEP 5) menyalin `kelas_id`
**mentah dari tahun ajaran sumber**, padahal sejak STEP 5 setiap
tahun ajaran punya baris kelas sendiri-sendiri. Kalau paket ini
belum dipasang dan Anda sudah pernah menjalankan fitur "Salin
Mapping" setelah STEP 5, **mapping/jadwal hasil salinan tsb kemungkinan
menunjuk ke kelas TAHUN AJARAN YANG SALAH**. Setelah paket ini
dipasang, jalankan ulang proses salin (aman, tidak akan
menduplikasi yang sudah benar) untuk periode yang terpengaruh, atau
periksa manual mapping/jadwal yang dihasilkan fitur salin sejak STEP 5.

## PENTING — routes/web.php

Perubahan STEP 6 di file ini hanya **1 baris baru**:

```php
Route::get('tahun-ajaran-duplikasi/preview', [TahunAjaranController::class, 'previewDuplikasiMapping'])->name('tahun-ajaran.duplikasi.preview');
```

Ditambahkan sebelum baris `tahun-ajaran.duplikasi`. Kalau ragu, cukup
salin 1 baris ini ke file Anda.

## Cara pasang

1. Backup project & database (standar, tidak ada migrasi kali ini).
2. Timpa 8 file di atas.
3. **Tidak perlu** `php artisan migrate` — tidak ada migrasi baru.

## Fitur baru / perbaikan yang bisa dicek

### 1. Salin Mapping & Jadwal — sekarang benar + ada Preview
- Kelas tujuan sekarang dicari lewat **(tingkat, nama_kelas) pada
  tahun ajaran TUJUAN**, bukan `kelas_id` sumber apa adanya.
- Kalau kelas dengan nama & tingkat yang sama belum ada di tujuan,
  baris itu **dilewati** dan disebutkan jelas di halaman preview
  (bukan dibuat dengan data tidak lengkap).
- Sebelum benar-benar menyalin, sekarang ada **halaman Preview**
  yang menampilkan: berapa yang akan disalin, berapa yang sudah ada
  (dilewati), berapa yang dilewati karena kelasnya belum tersedia —
  lengkap dengan daftar detailnya. Baru setelah itu ada tombol
  "Salin Sekarang" / "Batal".

### 2. Kelas Bentrok pada Jadwal — sekarang ditolak eksplisit
Sebelumnya, menambah jadwal baru untuk slot (kelas+hari+jam) yang
sudah terisi akan **diam-diam menimpa** jadwal lama (karena pakai
`updateOrCreate`). Sekarang ditolak dengan pesan jelas — mengedit
jadwal yang sudah ada harus lewat tombol "✏️ Edit", bukan menambah
baru di slot yang sama.

### 3. Guru Mengajar & Jadwal — sekarang bisa lihat histori
Kedua halaman sekarang punya **pemilih Periode** (default: periode
aktif). Memilih periode lain menampilkan data periode itu secara
**read-only** (tombol tambah/edit/hapus otomatis disembunyikan) —
data tidak pernah tercampur antar periode.

## Testing yang disarankan (sesuai brief STEP 6)

1. Buat mapping "Guru A - Matematika - 7A" di 2026/2027 Semester 2.
2. Buka menu Tahun Ajaran → "Salin Mapping Guru/Jadwal" → pilih dari
   2026/2027 S2 ke 2027/2028 S1 → klik "Lihat Preview".
3. Cek preview: kalau kelas 7A untuk 2027/2028 belum ada, harus
   muncul di daftar "kelasnya belum ada di tujuan" — BUKAN diproses
   dengan kelas yang salah.
4. Buat dulu kelas 7A untuk 2027/2028 (menu Data Kelas), ulangi
   preview → sekarang harus muncul di daftar "akan disalin" dengan
   kelas tujuan yang BENAR (ID kelas 2027/2028, bukan 2026/2027).
5. Klik "Salin Sekarang" → cek mapping baru menunjuk ke kelas
   2027/2028, dan mapping asal di 2026/2027 S2 tidak berubah.
6. Jalankan salin yang sama dua kali → pastikan tidak terjadi
   duplikasi (baris kedua otomatis masuk "sudah ada").
7. Buat jadwal Senin Jam 1 - Matematika - 7A - Guru A. Coba tambah
   jadwal LAIN untuk kelas 7A, hari & jam yang sama → harus ditolak
   ("Kelas ini sudah punya jadwal...").
8. Coba jadwalkan Guru A di 2 kelas berbeda pada hari & jam yang sama
   dalam periode yang sama → harus ditolak. Tapi Guru A boleh
   mengajar jam yang sama pada periode BERBEDA (2026/2027 vs
   2027/2028) — tidak dianggap bentrok.
9. Di menu Guru Mengajar / Jadwal, pilih periode 2026/2027 Semester 2
   dari dropdown Periode → pastikan datanya masih ada, tombol
   tambah/edit/hapus otomatis hilang (read-only, karena bukan
   periode aktif).

## Catatan

- Tidak ada perubahan struktur Guru Mengajar/Jadwal (`tahun_ajaran_id`
  yang sudah ada sejak awal project SUDAH cukup — sesuai instruksi
  brief, tidak ditambahkan `semester_id` terpisah).
- "Ruang bentrok" (Bagian 7) TIDAK diimplementasikan — project ini
  tidak memiliki konsep ruangan/kelas fisik sama sekali di skema
  database manapun, dan brief menyebutnya kondisional ("jika jadwal
  menggunakan ruangan").
- Dropdown filter "Semua Kelas" di halaman Guru Mengajar, saat
  melihat periode HISTORIS, masih menampilkan daftar kelas tahun
  ajaran AKTIF (bukan kelas tahun yang sedang dilihat) — keterbatasan
  kecil yang dicatat untuk STEP berikutnya; TIDAK memengaruhi data
  listing utama yang sudah benar-benar terpisah per periode.
