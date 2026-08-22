# Perbaikan Performa: Status WhatsApp Ortu, Pantau Pelanggaran, Dashboard

## Isi paket (6 file)

```
app/Services/PoinSiswaService.php
app/Http/Controllers/BkDashboardController.php
app/Http/Controllers/BkSiswaController.php
app/Http/Controllers/NotifikasiWhatsappController.php
app/Http/Controllers/DashboardController.php
database/migrations/2026_08_22_000001_add_index_tanggal_notifikasi_alfa_terkirims.php   (BARU)
```

## Akar masalah yang ditemukan

### 1. N+1 query di `PoinSiswaService::ringkasan()`
Method ini menjalankan **±9 query database untuk 1 siswa** (total
pelanggaran, total pengurangan, tahap saat ini, status, jumlah kasus,
jumlah pembinaan — beberapa di antaranya bahkan dihitung ULANG di
dalam method yang sama). Method ini dipanggil **di dalam loop per
baris siswa** di 3 halaman:

- **Dashboard BK "Pantau Pelanggaran"** (`BkDashboardController`) —
  dipanggil di **5 tempat berbeda** untuk membangun 5 daftar berbeda,
  siswa yang sama bisa dihitung ulang di setiap daftar.
- **"Monitoring Siswa" BK** (`BkSiswaController`).

Untuk sekolah dengan puluhan/ratusan siswa yang punya catatan
pelanggaran, ini bisa jadi **ribuan query** dalam 1 kali buka halaman
— itulah sebabnya kedua halaman ini terasa sangat lambat.

**Perbaikan:** `PoinSiswaService` sekarang punya method baru
`ringkasanBanyak(array $siswaIds)` yang menghitung ringkasan poin
untuk **banyak siswa sekaligus** dalam jumlah query **TETAP** (~4
query total, berapa pun jumlah siswanya) memakai `GROUP BY`, bukan
lagi query berulang per siswa. `ringkasan()` yang lama TETAP ADA
(dipakai apa adanya untuk halaman yang memang cuma butuh 1 siswa,
misalnya halaman profil siswa) — tidak ada perilaku yang berubah,
hanya caranya dihitung untuk banyak siswa sekaligus.

### 2. `whereMonth()`/`whereYear()` mencegah index dipakai
Di **"Status WhatsApp Ortu"** (`NotifikasiWhatsappController`),
filter bulan memakai `whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)`.
Membungkus kolom dengan fungsi seperti ini membuat MySQL **tidak bisa
memakai index** pada kolom tsb — jadi setiap kali halaman ini dibuka,
MySQL **scan seluruh tabel** `notifikasi_alfa_terkirims`, makin lambat
seiring tabelnya membesar dari bulan ke bulan.

**Perbaikan:** diganti `whereBetween('tanggal', [$awalBulan, $akhirBulan])`
(hasilnya identik, tapi bisa memakai index), + migrasi baru menambah
index pada kolom `tanggal` (sebelumnya cuma ada index gabungan
`siswa_id + tanggal` yang tidak berguna untuk filter tanggal lintas
semua siswa).

### 3. N+1 query per kelas di Dashboard (semua role)
Di `DashboardController`, dashboard **Admin**, **Kesiswaan**, dan
**Guru BK** semuanya menjalankan 1-2 query TAMBAHAN **di dalam loop**
untuk setiap kelas (mis. "apakah kelas ini sudah diabsen hari ini?",
"berapa yang Alfa hari ini?") — kalau sekolah punya 20-30 kelas, itu
20-60 query ekstra tiap kali dashboard dibuka. Wali Kelas juga
sebelumnya di-load satu-satu per kelas (lazy load).

**Perbaikan:** semua dijadikan 1 query `GROUP BY` yang menghitung
untuk SEMUA kelas sekaligus, dan Wali Kelas di-eager-load
(`->with('waliKelas')`).

## Cara pasang

1. Timpa 5 file controller/service di atas.
2. Tambahkan file migrasi baru.
3. Jalankan `php artisan migrate`.
4. **Jalankan juga** (kalau belum pernah / sudah lama tidak dijalankan
   di server produksi):
   ```
   php artisan view:clear
   php artisan config:clear
   php artisan cache:clear
   ```

## Catatan soal Icon (belum bisa saya pastikan tuntas)

Saya audit kode & hasil build FontAwesome Anda (Vite + self-hosted,
bukan CDN) — **secara teknis semuanya sudah benar** (font tersedia,
path benar, CSS custom property FontAwesome utuh, tidak corrupt).

Saya menemukan **27 file cache view Laravel** (`storage/framework/views/`)
di project yang saya terima — kalau ini dibuat SEBELUM perubahan icon
terakhir, Laravel akan tetap menyajikan versi lama. Jalankan
`php artisan view:clear` (sudah termasuk di langkah 4 di atas) di
server Anda dulu, lalu cek lagi.

Kalau setelah itu icon MASIH belum muncul, kemungkinan besar
`public/build` di server Anda belum pernah di-build ulang (folder ini
sengaja tidak ikut ke git — cek `.gitignore` Anda). Jalankan di
server:
```
npm install
npm run build
```

Kalau setelah KEDUA langkah di atas icon masih belum muncul, kirim
saya screenshot tab **Network** di DevTools browser Anda (cari request
yang gagal/404, biasanya nama file `.css`/`.woff2`) supaya saya bisa
pastikan penyebabnya lebih spesifik — saya tidak bisa menjalankan
`npm run build` sendiri di sandbox saya untuk memverifikasi build
akhir (dibatasi akses jaringan ke registry npm), jadi bagian ini
butuh konfirmasi Anda di server sungguhan.

## Testing yang disarankan

1. Buka "Pantau Pelanggaran" (Dashboard BK) → cek waktu loadnya
   sebelum & sesudah (kalau punya akses ke Laravel Debugbar / query
   log, bandingkan jumlah query-nya — harusnya turun drastis).
2. Buka "Status WhatsApp Ortu" → ganti-ganti filter bulan → cek makin
   responsif, terutama kalau datanya sudah beberapa bulan/tahun.
3. Login sebagai Admin, Kesiswaan, dan Guru BK satu-satu → cek
   Dashboard masing-masing lebih cepat, terutama kalau sekolah Anda
   punya banyak kelas.
4. Pastikan ANGKA yang ditampilkan (jumlah siswa, poin aktif, status,
   alfa hari ini, dst) tetap SAMA seperti sebelumnya — perbaikan ini
   murni soal kecepatan, bukan mengubah hasil perhitungan.
