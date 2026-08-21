# STEP 8 — Final User Flow Admin

Tidak ada migrasi baru — murni penyederhanaan alur & perbaikan UI di
atas struktur STEP 1-7 yang sudah ada.

## Isi paket (5 file, semua TIMPA)

```
app/Http/Controllers/TahunAjaranController.php
routes/web.php   ⚠️ lihat catatan di bawah
resources/views/tahun-ajaran/index.blade.php
resources/views/tahun-ajaran/persiapan.blade.php   (BARU)
resources/views/layouts/app.blade.php
```

## PENTING — routes/web.php

Perubahan STEP 8 di file ini hanya **1 baris baru**:

```php
Route::get('tahun-ajaran/{tahunAjaran}/persiapan', [TahunAjaranController::class, 'persiapan'])->name('tahun-ajaran.persiapan');
```

Ditambahkan setelah baris `tahun-ajaran.buat-baru`.

## Yang baru / diperbaiki

### 1. Halaman "Persiapan Tahun Ajaran Baru" (fitur utama STEP 8)
Satu halaman yang merangkum SEMUA status persiapan tahun ajaran baru
dalam satu tempat (tidak perlu buka banyak menu terpisah):
- ✅ **Wajib**: Tahun Ajaran & Semester 1/2 sudah dibuat.
- 📋 **Dibutuhkan sebelum operasional**: jumlah kelas, jumlah siswa
  yang sudah ditempatkan.
- 🎓 **Kenaikan Kelas**: tabel per kelas asal — berapa siswa sudah
  diproses, berapa belum (kalau ada yang belum, ditampilkan jelas,
  tidak didiamkan).
- 🧑‍🏫 **Wali Kelas**: tabel per kelas — sudah/belum ada wali kelasnya.
- 👨‍🏫🗓️ **Guru Mengajar & Jadwal**: ringkasan status lengkap/belum.
- Tombol **"✅ Aktifkan Tahun Ajaran"** di bagian bawah.

**Checklist ini murni panduan** — tidak ada satu pun item yang
memblokir tombol Aktifkan (sesuai instruksi eksplisit: jangan
memaksa admin mengisi semua). Syarat aktivasi yang SUNGGUHAN (tahun
ajaran lama harus terkunci penuh) tetap ditegakkan oleh `aktifkan()`
yang sudah ada sejak STEP 4 — tidak dibuat mekanisme validasi kedua.

Halaman ini otomatis terbuka begitu admin selesai membuat Tahun
Ajaran baru (sebelumnya kembali ke tabel biasa dan admin harus
mencari sendiri).

### 2. Bug diperbaiki: badge periode aktif hilang di HP
Header aplikasi (tampil di SETIAP halaman) punya badge "📅 Tahun
Ajaran · Semester" — tapi sebelumnya **disembunyikan di layar kecil**
(`hidden sm:inline-flex`). Karena guru/wali kelas paling sering akses
lewat HP, mereka justru TIDAK PERNAH melihat info periode ini.
Sekarang badge selalu tampil (versi ringkas di HP, lengkap di
desktop), termasuk badge "⚠️ Belum ada periode aktif" kalau memang
belum ada yang aktif.

## Cara pasang

1. Timpa 5 file di atas (perhatikan catatan `routes/web.php`).
2. Tidak perlu `php artisan migrate`.
3. Buka menu **Tahun Ajaran** → kartu "Tahun Ajaran Berikutnya"
   sekarang punya tombol "📋 Lihat Persiapan" kalau tahun berikutnya
   sudah dibuat.

## Audit yang dilakukan (sesuai Bagian 1, tidak semua menghasilkan perubahan kode)

- **Satu sumber periode aktif**: dikonfirmasi ulang, semua controller
  masih konsisten memakai `TahunAjaran::aktif()` — tidak ditemukan
  pola `latest()` atau logika periode aktif ganda.
- **Konsistensi istilah status**: dicek seluruh penggunaan "Nonaktif"
  di aplikasi — semuanya untuk status User/Siswa/Jenis Pelanggaran/Jam
  Pelajaran (konsep BEDA dari status periode), bukan kebocoran istilah
  periode yang tidak konsisten. Istilah periode (Akan Datang/Aktif/
  Selesai/Terkunci) sudah konsisten di seluruh halaman terkait periode
  sejak STEP 1-7 — tidak perlu diubah.
- **Redundansi menu**: tidak ditemukan menu terpisah dengan fungsi
  yang sama untuk pengelolaan periode — semuanya sudah terpusat di
  menu Tahun Ajaran sejak STEP 1-4.
- **Riwayat Periode**: tabel di halaman Tahun Ajaran (menampilkan
  semua periode + status Aktif/Selesai/Terkunci) SUDAH memenuhi
  kebutuhan ini — sengaja TIDAK dibuat halaman terpisah supaya tidak
  redundan (sesuai Bagian 21).
- **Permission**: dicek ulang — Admin & Kurikulum akses penuh modul
  periode, Guru tidak punya akses ubah pengaturan periode sama
  sekali (route middleware), Wali Kelas read-only. Tidak ditemukan
  celah baru.

## Yang TIDAK dikerjakan (di luar cakupan realistis untuk 1 sesi)

- Halaman "Guru Mengajar" & "Jadwal" belum punya ringkasan status
  standalone terpisah (Bagian 13/14 contoh mockup) — informasi yang
  sama sekarang tersedia lewat halaman Persiapan Tahun Ajaran, jadi
  tidak dibuat duplikat.
- Audit responsive menyeluruh di SEMUA halaman aplikasi tidak
  dilakukan (di luar cakupan STEP 8 yang membatasi hanya halaman
  terkait periode) — hanya header (dipakai di semua halaman) dan
  halaman baru Persiapan yang diverifikasi mobile-friendly.
