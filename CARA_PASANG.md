# Alat Diagnostik — Supaya Perbaikan Selanjutnya Tepat Sasaran

## Kenapa saya berhenti menebak

Sudah 4 putaran perbaikan yang **semuanya terverifikasi benar secara
kode** (N+1 query dihilangkan, index ditambahkan, subquery mahal
diganti WHERE biasa, caching dipasang di titik-titik yang dipanggil
puluhan kali, pagination ditambahkan). Kalau setelah semua itu masih
terasa kurang cepat, saya **tidak bisa lagi menebak dengan efektif**
tanpa tahu angka aslinya — saya tidak punya akses ke server/komputer
Anda untuk melihat sendiri berapa lama sebenarnya waktu yang habis,
dan di bagian mana.

## Isi paket (2 file)

```
app/Http/Middleware/QueryDebugBadge.php   (BARU)
bootstrap/app.php   (TIMPA — cuma 1 baris ditambah)
```

## Apa yang dilakukan

Menampilkan **badge kecil di pojok kiri bawah setiap halaman** berisi:
- Berapa banyak query database yang dijalankan.
- Berapa milidetik total dihabiskan DI DALAM database.
- Berapa milidetik dihabiskan DI LUAR database (PHP, rendering view, dll).
- Total waktu keseluruhan.

**100% aman & tidak aktif secara default** — hanya muncul kalau
`APP_DEBUG=true` (sudah begitu di local Anda) DAN Anda menambahkan 1
baris baru secara sadar di `.env`.

## Cara pasang & pakai

1. Timpa/tambahkan 2 file di atas.
2. Tambahkan baris ini di `.env`:
   ```
   DEBUG_QUERY_BADGE=true
   ```
3. `php artisan config:clear`
4. Buka **Pantau Pelanggaran**, lalu **Status WhatsApp Ortu**, lalu
   **Dashboard** (login sebagai beberapa role berbeda kalau bisa:
   Admin, Guru, Guru BK).
5. Baca badge di pojok kiri bawah tiap halaman, **catat 4 angkanya**,
   dan kirimkan ke saya (screenshot juga boleh).
6. Setelah selesai, hapus baris `DEBUG_QUERY_BADGE=true` dari `.env`
   (atau ganti `false`) supaya badge tidak muncul lagi.

## Cara membaca angkanya

- **Kalau waktu "di luar database" jauh lebih besar dari waktu "di
  database"** → bottleneck-nya BUKAN query lagi, kemungkinan besar
  Xdebug aktif, OPcache belum aktif, atau ada proses PHP berat di
  luar query (View rendering yang kompleks, dll). Ini akan
  mengarahkan saya untuk fokus ke area yang BENAR.
- **Kalau jumlah query masih besar (>30) di salah satu dari 3
  halaman itu** → berarti masih ada N+1 yang belum saya temukan di
  halaman itu spesifik — beri tahu saya jumlah persisnya, saya cari
  lagi lebih spesifik ke situ.
- **Kalau total waktu di badge sudah KECIL (di bawah ~200ms) tapi
  halamannya TETAP terasa lambat saat dibuka** → penyebabnya di luar
  Laravel sama sekali (jaringan lokal, antivirus yang scan tiap
  request, browser extension, dll) — bukan sesuatu yang bisa
  diperbaiki lewat kode aplikasi.

## Ringkasan semua yang SUDAH diperiksa & diperbaiki (4 putaran sebelumnya)

Supaya jelas ini bukan tebakan asal — berikut semua yang sudah
dicek dan dipastikan benar:

1. ✅ N+1 query di `PoinSiswaService` (9 query/siswa → batch tetap).
2. ✅ N+1 query per-kelas di Dashboard Admin/Kesiswaan/Guru BK.
3. ✅ `whereMonth()`/`whereYear()` di **11 file** → `whereBetween()` + index baru.
4. ✅ `TahunAjaran::aktif()` — 30+ pemanggilan tanpa cache → di-cache per-request.
5. ✅ `$user->kelasBk()` & `isWaliKelas()` — dipanggil berulang → di-cache per-instance.
6. ✅ `SESSION_DRIVER`/`CACHE_STORE` dari `database` → `file`.
7. ✅ `Kelas::aktif()` & `$user->kelasWali` — subquery EXISTS → `WHERE` langsung.
8. ✅ Pagination ditambahkan di Status WhatsApp Ortu (dulu tanpa batas).
9. ✅ Icon/warna Tailwind yang hilang (masalah terpisah, sudah selesai).
10. ✅ Middleware yang jalan di setiap request (`NoCacheHeaders`,
    `EnsureRole`, `EnsurePeriodeTidakTerkunci`) — dicek, tidak ada
    query berlebih di situ.
11. ✅ Tidak ada query-logging/debugbar lain yang aktif diam-diam.

Yang **belum bisa saya pastikan dari kode saja** (perlu Anda cek
langsung di komputer Anda):
- Xdebug aktif atau tidak (`php -v` — cari kata "Xdebug").
- OPcache aktif atau tidak (`php -m | grep -i opcache`).
- Spesifikasi & beban komputer Anda saat testing (aplikasi lain yang
  jalan bersamaan, dst).

Badge ini akan menjawab semuanya dengan pasti dalam 1x buka halaman.
