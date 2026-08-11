# Perbaikan: Jurnal/Absensi Tidak Berubah Saat Diisi Ulang

## Analisis jujur

Saya sudah telusuri logic penyimpanannya (`MengajarController::store()`)
baris per baris, termasuk:
- Cara mencari jurnal yang sudah ada saat diedit ulang
- Cara `AbsensiSiswa` disimpan per siswa

Secara logic, proses UPDATE ini **dilindungi constraint UNIQUE di
database** (`jurnal_mengajar_id` + `siswa_id`), yang membuat
`updateOrCreate` seharusnya SELALU meng-update baris yang sama, tidak
mungkin diam-diam gagal atau membuat data baru tanpa terlihat error.
Saya tidak menemukan bug logic yang jelas di jalur ini.

**Kemungkinan penyebab paling masuk akal**: browser menampilkan
halaman lama dari **cache** (terutama kalau memakai tombol Back,
atau membuka ulang tab yang sama tanpa refresh), bukan mengambil data
terbaru dari server. Ini sangat umum terjadi dan gejalanya persis
seperti yang Anda alami: data di database sebenarnya SUDAH berubah,
tapi tampilan di layar masih menunjukkan versi lama.

## Yang saya perbaiki

1. **`app/Http/Middleware/NoCacheHeaders.php`** (baru) — menambahkan
   header supaya browser TIDAK BOLEH menyimpan/menampilkan cache
   halaman untuk semua halaman yang butuh login (dashboard, Jurnal
   Kelas, Absensi & Jurnal Mengajar, dll). Jadi setiap kali halaman
   dibuka (termasuk lewat tombol Back), data yang diambil PASTI
   terbaru dari server.

2. **`bootstrap/app.php`** — mendaftarkan middleware di atas untuk
   semua halaman.

3. **`app/Http/Controllers/MengajarController.php`** —
   `cariJurnalUntukSesi()` diperkuat dengan jalur cadangan (fallback):
   kalau pencarian lewat tabel penghubung (`jurnal_mengajar_slots`)
   entah kenapa tidak ketemu, sistem otomatis coba cari langsung ke
   tabel `jurnal_mengajars`. Ini jaga-jaga murni (defensif), bukan
   berarti saya menemukan bug di situ — tapi memperkecil kemungkinan
   penyebab lain di masa depan.

## Cara pasang

1. Salin 3 file di atas ke project Anda (timpa yang lama).
2. Tidak perlu migration.
3. Clear cache:
   ```bash
   php artisan config:clear
   php artisan route:clear
   ```
4. **PENTING saat testing**: setelah pasang, lakukan **hard refresh**
   di browser (`Ctrl+Shift+R` di Chrome/Firefox Windows) minimal
   sekali di awal, supaya browser benar-benar membuang cache versi
   lama yang mungkin masih tersimpan dari sebelum perbaikan ini.

## Kalau masih terjadi setelah ini

Kalau setelah pasang & hard refresh masalahnya masih muncul, tolong
info detail berikut supaya saya bisa telusuri lebih spesifik:
1. Setelah simpan ulang, apakah muncul pesan sukses ("berhasil
   disimpan") di halaman?
2. Untuk melihat "tidak berubah"-nya, apakah Anda me-refresh halaman
   (F5) atau pakai tombol Back browser?
3. Kalau dicek langsung di database (tabel `absensi_siswas`, cari
   baris siswa & tanggal itu) — apakah `status`-nya benar sudah
   berubah di database, meski tampilan web belum?

Jawaban poin 3 akan sangat menentukan: kalau di database SUDAH
berubah tapi tampilan belum — pasti soal cache/tampilan (perbaikan
ini seharusnya sudah cukup). Kalau di database TERNYATA belum
berubah juga — berarti ada bug lain yang belum ketemu, dan saya perlu
gali lebih dalam dengan info itu.
