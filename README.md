# SIM-SPENGA
Sistem Informasi Manajemen Sekolah — *Memudahkan Guru dan Monitoring Siswa Beserta Guru*

Dibangun dengan **Laravel 13**, MySQL, Tailwind CSS + Vite, Alpine.js, dan Livewire (khusus `wire:navigate` — pindah halaman tanpa muat ulang).

---

## ⚠️ Penting sebelum mulai

Project ini berisi seluruh **source code aplikasi** (models, controllers, migrations, seeders, routes, views).
Karena dibuat di lingkungan tanpa akses internet, folder `vendor/` (dependency Composer) **belum ter-install**.
Ikuti langkah di bawah untuk menjalankannya di komputer/server Anda.

## 🚀 Cara Menjalankan

### 1. Persiapan
- PHP >= 8.3
- Composer
- MySQL / MariaDB
- **Node.js** — wajib. Sejak Tailwind & Font Awesome di-bundle Vite (tidak lagi dari CDN), aset harus dibangun sebelum aplikasi dipakai.

### 2. Install dependency
```bash
cd sim-spenga
composer install
```

### 3. Konfigurasi environment
```bash
cp .env.example .env
php artisan key:generate
```
Edit `.env`, sesuaikan `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` dengan MySQL Anda. Buat database kosong terlebih dahulu, contoh:
```sql
CREATE DATABASE sim_spenga;
```

### 4. Migrasi & data awal (seeder)
```bash
php artisan migrate --seed
```
Perintah ini akan membuat seluruh tabel **dan** mengisi data awal: akun demo, tahun ajaran, kelas 7A-9F, mata pelajaran, jam pelajaran ke-1 s.d ke-8, dan contoh siswa di kelas 7A & 7B.

### 5. Bangun aset frontend
```bash
npm install
npm run build
```
Wajib. Tanpa langkah ini halaman akan tampil tanpa gaya sama sekali, karena Tailwind dan Font Awesome
kini di-bundle Vite (tidak lagi diambil dari CDN).

### 6. Jalankan aplikasi
```bash
php artisan serve
```
Buka `http://localhost:8000`.

---

## 🔑 Akun Demo (password semua: `password`)

| Role | Email | Keterangan |
|---|---|---|
| Admin | admin@spenga.sch.id | Akses penuh ke semua data |
| Kepala Sekolah | kepsek@spenga.sch.id | Monitoring menyeluruh (read-only) |
| Kurikulum | kurikulum@spenga.sch.id | Mapping guru, jadwal, rekapitulasi |
| Guru / Wali Kelas 7A | guru1@spenga.sch.id | Wali kelas 7A |
| Guru / Wali Kelas 7B | guru2@spenga.sch.id | Wali kelas 7B |
| Guru Mapel | guru3@spenga.sch.id, guru4@spenga.sch.id | Guru mapel biasa |

> Catatan: seeder hanya membuat user & kelas dasar. Untuk mencoba alur mengajar penuh, tambahkan **Mapping Guru Mengajar** dan **Jadwal Pelajaran** lewat menu Kurikulum (login sebagai `kurikulum@spenga.sch.id` atau `admin@spenga.sch.id`).

---

## 🧭 Alur Kerja Sesuai Kebutuhan

1. **Admin** membuat Tahun Ajaran aktif & Jam Pelajaran (menu *Jam Pelajaran* — fleksibel, waktu bisa diubah kapan saja dan otomatis berlaku di semua jadwal/absensi).
2. **Kurikulum**:
   - Input **Mapping Guru Mengajar** (guru mana mengajar mapel apa di kelas 7A-7F/8A-8F/9A-9F) — manual atau **import Excel**.
   - Susun **Jadwal Pelajaran** per kelas per hari (Senin-Sabtu) — manual atau **import Excel**.
3. **Guru Mapel** login → menu *Absensi & Jurnal Mengajar* → pilih hari → pilih kelas & jam ke sesuai jadwalnya → isi **Jurnal Mengajar** + **Absensi Siswa** dalam satu form. Data ini otomatis terhubung ke Jurnal Kelas & Absensi Kelas milik Wali Kelas, serta muncul di dashboard monitoring Kurikulum & Kepala Sekolah.
4. **Wali Kelas** (guru yang ditandai sebagai wali suatu kelas) dapat membuka *Rekap Absensi Bulanan* — 1 lembar berisi NIS, Nama, kolom tanggal 1-31, kolom Sakit/Izin/Alfa, dan Jumlah, bisa dipilih bulan apapun sepanjang tahun ajaran. Juga dapat memantau *Jurnal Mengajar Kelas*.
5. **Kepala Sekolah** melihat dashboard ringkasan sekolah menyeluruh (jumlah siswa/guru/kelas, status pengisian absensi tiap kelas hari ini).
6. **Admin** dapat mengakses seluruh menu tanpa batasan role.

---

## 📥 Format Import Excel

### Import Mapping Guru Mengajar (`kurikulum/guru-mengajar/import`)
Kolom header: `nip_guru`, `kode_kelas`, `kode_mapel`
```
nip_guru            kode_kelas  kode_mapel
198501012010011001  7A          MTK
```

### Import Jadwal Pelajaran (`jadwal/import`)
Kolom header: `hari`, `kode_kelas`, `jam_ke`, `kode_mapel`, `nip_guru`
```
hari    kode_kelas  jam_ke  kode_mapel  nip_guru
Senin   7A          1       MTK         198501012010011001
```

### Import Data Siswa (`siswa-import`)
Kolom header: `nis`, `nisn`, `nama`, `jenis_kelamin`, `kode_kelas`
```
nis     nisn         nama            jenis_kelamin  kode_kelas
24101   1024101001   Ahmad Fadillah  L              7A
```

Baris yang referensinya (NIP guru / kode kelas / kode mapel) tidak ditemukan di database akan otomatis dilewati.

---

## 🗂️ Struktur Basis Data (ringkas)

- `users` — semua pengguna (admin, kepala_sekolah, kurikulum, guru, guru_bk, kesiswaan, tu). Wali kelas dicatat di tabel tersendiri `penugasan_wali_kelas`, karena penugasannya **berlaku per semester** — guru bisa menjadi wali kelas 7A di Semester Ganjil dan tidak lagi di Genap.
- `tahun_ajarans`, `kelas`, `siswas`, `mata_pelajarans`, `jam_pelajarans`
- `guru_mengajar_kelas` — mapping guru ⇄ kelas ⇄ mapel (input Kurikulum)
- `jadwal_pelajarans` — jadwal per hari/kelas/jam (input Kurikulum)
- `jurnal_mengajars` — jurnal mengajar guru per pertemuan
- `absensi_siswas` — absensi per siswa per pertemuan, terhubung ke `jurnal_mengajars`

---

## 🎨 Teknologi
- Laravel 13 (PHP 8.3+)
- MySQL
- Tailwind CSS + Vite (di-bundle & di-self-host — tidak bergantung CDN mana pun)
- Alpine.js untuk interaktivitas ringan (dropdown, toggle form, radio absensi)
- Livewire — dipakai HANYA untuk `wire:navigate`; tidak ada satu pun halaman yang ditulis sebagai komponen Livewire
- Maatwebsite/Excel untuk fitur import Excel
- Flutter (folder `mobile/`) untuk aplikasi Android

## 📌 Pengembangan Lanjutan yang Disarankan
- Tambahkan export PDF/Excel untuk rekap absensi bulanan (tombol cetak saat ini memakai `window.print()`).
- Tambahkan notifikasi otomatis ke Wali Kelas jika ada siswa Alfa berturut-turut.
- Tambahkan grafik tren kehadiran per kelas di dashboard Kepala Sekolah.

---

## 📚 Dokumentasi lengkap

Dua panduan siap cetak ada di folder `docs/`, dibangkitkan dari skrip PHP di folder yang sama
(`php docs/_pembuat-panduan.php`). Ubah skripnya, jangan menyunting `.docx`-nya langsung.

| Berkas | Untuk siapa | Isinya |
|---|---|---|
| `docs/Panduan-SIM-SPENGA.docx` | Semua pengguna | Pemakaian menu harian per peran, aplikasi Android, pergantian semester |
| `docs/Panduan-Database-Admin.docx` | Admin | Mencadangkan, memulihkan, mengosongkan, merawat database |

## 🔐 Lisensi & mode pemasangan

Aplikasi punya dua mode, diatur lewat `LISENSI_MODE` di `.env`:

- **`lokal`** — nomor seri diperiksa di dalam aplikasi sendiri. Untuk sekolah yang memasang di servernya sendiri.
- **`server`** — masa aktif diperiksa ke server lisensi FF Production. Untuk pemasangan yang dikelola FF Production;
  sekolah tidak pernah mengetik apa pun. Perpanjangan cukup dilakukan dari sisi server, dan aplikasi membuka
  sendiri pada sapaan berikutnya.

Perintah terkait:

```bash
php artisan lisensi:sapa          # memperbarui surat lisensi (dijadwalkan cron)
php artisan lisensi:buat-kunci    # membuat sepasang kunci Ed25519 (dijalankan FF Production)
```

## 💾 Cadangan data

```bash
php artisan backup:buat                    # database + storage/app + APP_KEY, terenkripsi
php artisan backup:buka <berkas.simbak>    # membukanya kembali
```

Isi `BACKUP_SANDI` di `.env` lebih dulu — perintahnya menolak berjalan tanpa itu, karena berkas backup
berisi seluruh data siswa beserta APP_KEY. Simpan kata sandinya **di luar** komputer server.

## 📱 Aplikasi Android

Ada di folder `mobile/` (Flutter). Berisi layar masuk native — lengkap dengan sidik jari dan lupa
kata sandi lewat WhatsApp — lalu menampilkan aplikasi web ini di dalam WebView.

```bash
cd mobile
flutter build apk --release
```

Karena isinya aplikasi web yang sama, **perbaikan di server langsung sampai ke ponsel tanpa memasang
ulang APK**. Pemasangan ulang hanya perlu bila kode di `mobile/lib` yang berubah.
