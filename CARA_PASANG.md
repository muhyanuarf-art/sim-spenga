# Penyederhanaan Halaman Tahun Ajaran

Tidak ada migrasi baru. 4 file, semua TIMPA.

## PENTING — 1 FILE HARUS DIHAPUS

```
resources/views/tahun-ajaran/persiapan.blade.php   → HAPUS file ini dari project Anda
```

Halaman "Persiapan Tahun Ajaran" (dibuat sebelumnya) sudah dihapus
sesuai permintaan — file view-nya sudah tidak dipakai sama sekali,
harus dihapus manual dari server Anda.

## Isi paket (4 file, semua TIMPA)

```
app/Http/Controllers/TahunAjaranController.php
routes/web.php   ⚠️ lihat catatan di bawah
resources/views/tahun-ajaran/index.blade.php
resources/views/tahun-ajaran/preview-duplikasi.blade.php
```

## PENTING — routes/web.php

2 baris route DIHAPUS (fitur terkait sudah tidak ada):
```php
Route::get('tahun-ajaran/{tahunAjaran}/persiapan', ...)->name('tahun-ajaran.persiapan');
Route::post('tahun-ajaran/{tahunAjaran}/ganti-semester', ...)->name('tahun-ajaran.ganti-semester');
```
Tidak ada baris baru ditambahkan — "Salin Data" dan "Tambah Semester"
memakai ulang route yang sudah ada (`tahun-ajaran.duplikasi.preview`
dan `tahun-ajaran.store`).

## Perubahan sesuai 6 permintaan

### 1. Tombol "Tutup Semester X & Aktifkan Semester Y" DIHAPUS
Sekarang pergantian semester murni 2 langkah terpisah yang sudah ada
sebagai tombol sendiri-sendiri di tabel: **"🔒 Tutup Semester"**
(pada semester yang aktif) lalu **"✅ Aktifkan"** (pada semester
berikutnya). Tidak ada lagi tombol gabungan yang membingungkan.

### 2. Kolom & input Tanggal DIHAPUS
Kolom "Tanggal" di tabel dan input "Tanggal Mulai/Selesai" di semua
form (Tambah, Edit, Buat Tahun Ajaran) dihapus — memang tidak dipakai
validasi apa pun. Kolom di database TETAP ADA (tidak diubah), hanya
tidak lagi ditanyakan/ditampilkan di UI.

### 3. Tombol "Persiapan" DIHAPUS
Halaman & tombolnya dihapus total.

### 4. Tombol "+ Tambah Semester" BARU
Muncul otomatis di baris manapun yang tahun ajarannya baru punya 1
semester (Ganjil ATAU Genap saja) — tinggal klik untuk melengkapi
semester yang kurang. Hanya Ganjil/Genap yang mungkin (tidak bisa
lebih dari 2 semester per tahun ajaran).

### 5. "Tutup Semester" mengunci SEMUA data (tidak berubah — dikonfirmasi)
Perilaku ini sudah benar sejak STEP 2-7 (memakai
`PeriodeAkademik::pastikanTidakTerkunci()` yang dicek di SEMUA modul
transaksi — jurnal, absensi, jadwal, guru mengajar, BK). Teks
konfirmasi tombol diperjelas untuk menegaskan ini. Data yang sudah
ditutup TETAP bisa dipakai sebagai sumber "Salin Data".

### 6. "📋 Salin Mapping Guru/Jadwal" DIGANTI "📋 Salin Data" per baris
Sekarang setiap baris Tahun Ajaran/Semester di tabel punya tombol
**"📋 Salin Data"** sendiri. Alurnya:
1. Klik "Salin Data" → pilih tujuan → konfirmasi "Anda akan menyalin
   data..." → Ya.
2. Muncul halaman **Preview** — checklist lengkap apa yang akan
   disalin: **Kelas & Wali Kelas** (BARU — sebelumnya cuma Guru
   Mengajar & Jadwal, sekarang digabung jadi satu alur), Guru
   Mengajar, dan Jadwal. Ada catatan: *"Kelas & Wali Kelas disalin
   sebagai titik awal — sesuaikan lagi kalau ada perubahan wali kelas
   untuk periode ini."*
3. Klik "Salin Sekarang" → tersimpan → muncul pesan jelas berapa
   kelas/mapping/jadwal yang berhasil disalin.
4. Setelahnya tekan **"✅ Aktifkan"** pada semester tujuan seperti
   biasa.

Kelas yang belum ada di tujuan sekarang **otomatis dibuat** sebagai
bagian dari "Salin Data" ini (sebelumnya harus dibuat manual dulu di
menu Data Kelas, baru bisa menyalin Guru Mengajar/Jadwal — sekarang
satu langkah saja).

## Cara pasang

1. Timpa 4 file di atas.
2. **Hapus** `resources/views/tahun-ajaran/persiapan.blade.php`.
3. Tidak perlu `php artisan migrate`.

## Testing yang disarankan

1. Buka menu Tahun Ajaran → pastikan tidak ada lagi tombol gabungan
   Tutup+Aktifkan, tidak ada kolom Tanggal, tidak ada tombol Persiapan.
2. Buat Tahun Ajaran baru lewat "+ Tambah Tahun Ajaran" dengan HANYA
   1 semester (mis. Ganjil saja) → pastikan tombol "+ Tambah Semester
   Genap" muncul di baris itu → klik → pastikan Semester Genap
   langsung muncul sebagai baris baru.
3. Klik "📋 Salin Data" pada semester yang berisi data → pilih tujuan
   → pastikan muncul konfirmasi, lalu halaman Preview menampilkan 3
   bagian (Kelas & Wali Kelas, Guru Mengajar, Jadwal) dengan angka
   yang masuk akal.
4. Klik "Salin Sekarang" → pastikan kelas baru muncul di Data Kelas
   tujuan lengkap dengan wali kelasnya, mapping guru mengajar &
   jadwal juga tersalin ke kelas yang BENAR (bukan kelas tahun
   sumber).
5. Ulangi "Salin Data" yang sama sekali lagi → pastikan tidak ada
   duplikasi (semua masuk hitungan "sudah ada").
6. Klik "🔒 Tutup Semester" pada semester aktif → coba edit
   jurnal/jadwal/guru-mengajar di semester itu → harus ditolak.
7. Klik "✅ Aktifkan" pada semester berikutnya → pastikan berhasil
   (atau ditolak dengan pesan jelas kalau tahun ajaran lama belum
   ditutup penuh, sesuai mekanisme yang sudah ada sejak STEP 4).
