# Master Data Pelanggaran — Seeder Lengkap (78 jenis)

## Isi

| Kategori | Rentang Poin | Jumlah Jenis |
|---|---|---|
| Ringan | 5-15 | 27 |
| Sedang | 16-50 | 25 |
| Berat | 51-75 | 14 |
| Sangat Berat | 76-100 | 12 |
| **Total** | | **78** |

Semua sudah saya verifikasi otomatis: **tidak ada** poin yang di luar
rentang kategorinya, dan **tidak ada** kode yang dobel.

Daftar ini melengkapi 5 contoh yang ada di masing-masing kategori pada
dokumen sebelumnya, dengan jenis pelanggaran umum lain yang lazim
ditemui di sekolah menengah — mencakup soal kedisiplinan, sikap,
kejujuran akademik, kekerasan, hingga pelanggaran berat terkait hukum.
Kode & poin bisa disesuaikan lagi lewat menu **Data Pelanggaran
(Master)** kapan saja (tidak perlu jalankan seeder ulang untuk itu).

## File

| File | Keterangan |
|---|---|
| `database/seeders/JenisPelanggaranSeeder.php` | Seeder baru — isi 78 jenis pelanggaran |
| `database/seeders/DatabaseSeeder.php` | Didaftarkan supaya ikut jalan kalau nanti `db:seed` dipanggil tanpa `--class` |

## Cara pasang

1. Salin kedua file ke project Anda (timpa `DatabaseSeeder.php` yang lama).
2. Jalankan **salah satu** dari ini:
   ```bash
   # Cara A: jalankan seeder ini saja (paling aman, tidak menyentuh data lain)
   php artisan db:seed --class=JenisPelanggaranSeeder

   # Cara B: jalankan semua seeder (aman juga — user/kelas/siswa dst
   # yang sudah ada tidak akan dobel, karena seeder-seeder itu juga
   # sudah pakai updateOrCreate/firstOrCreate)
   php artisan db:seed
   ```
3. Cek menu **BK → Data Pelanggaran (Master)** — harus muncul 78 baris,
   terkelompok rapi per kategori.

**Aman dijalankan berkali-kali** — pakai `updateOrCreate` berdasarkan
`kode`, jadi kalau dijalankan ulang tidak akan membuat data dobel,
hanya memperbarui kalau ada perubahan nama/poin di kode yang sama.

## Kalau mau menyesuaikan

Kalau kebijakan sekolah punya poin yang beda dari yang saya susun di
sini (misal "Terlambat" maunya 3 poin bukan 5), ada 2 cara:
- **Cepat**: edit langsung lewat UI di menu Data Pelanggaran (Master)
  setelah di-seed.
- **Kalau mau ubah banyak sekaligus**: kabari saya poin/kategori mana
  saja yang mau disesuaikan, saya update seeder-nya sekali lagi.
