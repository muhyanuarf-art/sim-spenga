# Cara Menerapkan Perbaikan: Route Hilang + Fitur Salin Jam Pelajaran

Ada 2 cara — pilih salah satu saja (jangan dua-duanya).

---

## CARA A (disarankan): Terapkan lewat patch

Paling aman karena otomatis, tidak perlu copy-paste manual.

### 1. Taruh file patch
Simpan `perbaikan-route-dan-fitur.patch` ke folder project Anda, sejajar
dengan folder `app`, `routes`, dll (root project, misal `C:\laragon\www\sim-spenga`).

### 2. Cek dulu tidak ada perubahan lokal yang belum disimpan
```powershell
git status
```
Kalau ada perubahan yang belum di-commit dan tidak mau hilang, `git stash` dulu.

### 3. Terapkan patch
```powershell
git apply --whitespace=nowarn perbaikan-route-dan-fitur.patch
```

### 4. Bersihkan cache view Laravel (supaya perubahan Blade langsung terlihat)
```powershell
php artisan view:clear
php artisan route:clear
```

### 5. Commit (opsional)
```powershell
git add -A
git commit -m "Perbaiki route import Kelas/Mapel/template yang hilang + aktifkan fitur Salin Jam Pelajaran"
git push
```

---

## CARA B: Ganti manual file-nya satu per satu

Kalau `git apply` gagal (misal karena Anda sudah pernah mengubah file yang sama),
timpa file-file berikut dengan isi dari file yang saya berikan:

| File yang saya berikan          | Timpa ke file asli di project Anda                        |
|----------------------------------|-------------------------------------------------------------|
| `web.php`                        | `routes/web.php`                                            |
| `app.blade.php`                  | `resources/views/layouts/app.blade.php`                     |
| `kelas-index.blade.php`          | `resources/views/kelas/index.blade.php`                     |
| `mapel-index.blade.php`          | `resources/views/mapel/index.blade.php`                     |
| `jam-pelajaran-index.blade.php`  | `resources/views/jam-pelajaran/index.blade.php`              |

Setelah itu jalankan juga:
```powershell
php artisan view:clear
php artisan route:clear
```

Lalu **hapus manual** 2 file berikut dari root project (sudah tidak relevan,
patch/perbaikan di dalamnya sudah lama diterapkan ke kode):
- `perbaikan-notifikasi-wa-fonnte.patch`
- `CARA_TERAPKAN_PERBAIKAN.md`

---

## Apa saja yang diperbaiki

1. **Route `*.template` yang hilang** untuk Import Excel Jadwal, Mapping Guru
   Mengajar, dan Data Siswa — sebelumnya membuka halaman importnya langsung
   error, karena view memanggil `route('jadwal.template')` dkk yang tidak
   pernah didaftarkan.
2. **Fitur Import Excel Kelas & Mata Pelajaran** — controller & tampilannya
   sudah ada sejak awal tapi tidak pernah disambungkan ke route. Sekarang
   sudah bisa diakses, dan tombol "📥 Import Excel" sudah muncul di halaman
   Data Kelas & Mata Pelajaran.
3. **Fitur Salin Jam Pelajaran ke Hari Lain** — logic di
   `JamPelajaranController::salin()` sudah lengkap sejak awal, tapi tidak
   ada route maupun tombol di halaman. Sekarang sudah tersedia tombol
   "🔁 Salin ke Hari Lain" di menu Jam Pelajaran, lengkap dengan pilihan hari
   sumber dan hari tujuan (bisa lebih dari satu).
4. **Pesan error (`session('error')`) tidak pernah tampil** — layout hanya
   menampilkan pesan sukses, sehingga saat fitur Salin Jam Pelajaran (atau
   fitur lain di masa depan) mengembalikan pesan error, pengguna tidak
   melihat apa-apa. Sekarang pesan error ditampilkan dengan kotak merah,
   sama seperti pesan sukses.
5. **File patch lama yang sudah tidak relevan** (`perbaikan-notifikasi-wa-fonnte.patch`
   dan `CARA_TERAPKAN_PERBAIKAN.md`) dihapus dari repo — isinya sudah lama
   diterapkan ke kode, jadi hanya membingungkan kalau dibiarkan.

## Cara menguji setelah diterapkan

1. Login sebagai `kurikulum@spenga.sch.id` atau `admin@spenga.sch.id`.
2. Buka menu **Jadwal Pelajaran → Import Excel** → pastikan halaman terbuka
   normal (tidak error) dan tombol "Download Template Excel" berfungsi.
   Ulangi untuk **Data Siswa → Import Excel** dan **Mapping Guru Mengajar →
   Import Excel**.
3. Buka menu **Data Kelas** → pastikan ada tombol "📥 Import Excel" di kanan
   atas, klik, pastikan halamannya terbuka dan template bisa diunduh.
   Ulangi untuk menu **Mata Pelajaran**.
4. Login sebagai `admin@spenga.sch.id` → buka menu **Jam Pelajaran** →
   klik "🔁 Salin ke Hari Lain" → pilih hari sumber yang sudah ada jamnya
   dan satu/lebih hari tujuan → Salin Sekarang → pastikan muncul pesan
   sukses berwarna hijau dan data jam pelajaran di hari tujuan berubah
   sesuai hari sumber.
5. Coba salah satu kondisi gagal (misal hari sumber = hari tujuan tanpa
   pilihan lain, atau hari sumber belum punya jam pelajaran) → pastikan
   sekarang muncul pesan error berwarna merah (sebelumnya diam saja).
