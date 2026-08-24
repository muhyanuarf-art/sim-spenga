# 3 Perbaikan Cetak: Garis Tabel Hitam, Kolom "H", Pemadatan ke 1 Halaman

## 1. Garis tabel jadi hitam saat Cetak
Sebelumnya garis tabel abu-abu terang (`border-slate-200` dkk.) — sekarang
otomatis jadi hitam pekat saat Cetak/Export PDF, di kedua gaya tabel yang
dipakai aplikasi ini (tabel "grid" ala rekap absensi, dan tabel daftar
biasa). Di layar biasa tidak berubah, tetap abu-abu tipis seperti biasa.

## 2. Kolom "H" (jumlah Hadir) ditambahkan
Ditambahkan tepat sebelum kolom S di 3 laporan (semua laporan yang
punya kolom S/I/A/Jml, sama seperti sebelumnya nambah kolom No):
- Rekap Absensi Kelas (Wali Kelas)
- Absensi per Mata Pelajaran (Laporan Guru)
- Rekap Absensi Ekstrakurikuler (tabel Pembina & tabel Siswa, dua-duanya)

Urutan kolom sekarang: ... **H** | S | I | A | Jml.

## 3. Pemadatan supaya lebih mudah muat 1 halaman
- Jarak sebelum blok tanda tangan dikurangi saat print (`print:mt-10` →
  `print:mt-4`).
- Padding kartu & sel tabel sedikit dirapatkan KHUSUS saat print (tampilan
  di layar tidak berubah).

**Catatan jujur tentang batasannya** (biar tidak salah ekspektasi): ini
**memperbesar peluang** data + tanda tangan muat 1 halaman untuk kasus
yang sebelumnya cuma kelebihan tipis (mis. kurang 1-2 baris saja) — TAPI
bukan jaminan mutlak untuk semua ukuran data. Browser mengatur pembagian
halaman print murni berdasarkan tinggi konten yang benar-benar dirender;
tidak ada cara 100% andal via CSS untuk memaksa "kalau bisa dipadatkan
jadi 1 halaman, WAJIB jadi 1 halaman" tanpa mengetahui dulu berapa
persis tinggi kontennya (itu perlu diukur, dan hasil ukurnya beda-beda
tiap laporan/jumlah siswa/bulan). Kalau datanya memang panjang (kelas
besar, bulan 31 hari), otomatis tetap lanjut ke halaman berikutnya
seperti seharusnya — sesuai aturan yang Anda jelaskan sendiri (itu memang
benar & sudah didukung lewat perbaikan sebelumnya, blok tanda tangan
tidak lagi terpotong di tengah).

Kalau ke depan Anda butuh kontrol yang benar-benar presisi (mis. "wajib
1 halaman kalau totalnya di bawah 25 siswa"), itu perlu pendekatan
berbeda — beralih dari print browser biasa ke pembuatan PDF di sisi
server (pakai library seperti dompdf/mpdf) yang punya kendali page-break
lebih akurat. Kabari saja kalau mau saya kerjakan itu sebagai langkah
lanjutan.

## File yang diubah
- `resources/css/app.css` — 3 blok aturan print baru (border hitam, padatan).
- `resources/views/components/blok-tanda-tangan.blade.php` — margin print dikurangi.
- `resources/views/components/blok-tanda-tangan-dua.blade.php` — margin print dikurangi.
- `app/Http/Controllers/WaliKelasController.php` — hitung `hadir` di rekap.
- `app/Http/Controllers/LaporanGuruController.php` — hitung `hadir` di rekap.
- `app/Http/Controllers/EkskulRekapController.php` — hitung `hadir` di rekap (siswa & pembina).
- `resources/views/walikelas/absensi-bulanan.blade.php` — kolom H.
- `resources/views/laporan/absensi-guru.blade.php` — kolom H.
- `resources/views/ekstrakurikuler/rekap-bulanan.blade.php` — kolom H (2 tabel).

**Tidak ada migrasi baru.**

## Cara menerapkan (di Laragon)
1. Salin semua file di atas ke lokasi yang sama persis di
   `C:\laragon\www\sim-spenga`.
2. **Wajib** `npm run build` (karena `app.css` berubah).
3. Tidak perlu migrasi.
4. Test: buka salah satu dari 3 laporan yang dapat kolom H → pastikan
   angkanya benar (hadir = total sesi tercatat dikurangi S+I+A). Coba
   Cetak → pastikan garis tabel jadi hitam pekat, dan blok tanda tangan
   yang tadinya terpotong sekarang lebih sering muat jadi 1 halaman utuh.
