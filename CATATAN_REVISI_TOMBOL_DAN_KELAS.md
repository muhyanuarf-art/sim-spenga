# Revisi: Spasi tombol, ⋮ jadi tombol langsung, dan kolom Kelas di Rekap

## 1. Spasi ikon/teks/kotak yang bertabrakan
Ubin "Anggota/Absensi/Rekap" ditambah jaraknya: gap antar kotak diperbesar
(gap-2 → gap-3), padding dalam tiap kotak ditambah (py-3.5 → py-4, plus
px-2), dan jarak ikon-ke-teks diperbesar (gap-1.5 → gap-2) supaya tidak
lagi terlihat sempit/menyentuh.

## 2. Menu titik-tiga (⋮) diganti tombol langsung
Sebelumnya Edit/Hapus disembunyikan di balik menu dropdown (klik ⋮ dulu).
Sekarang keduanya jadi 2 tombol ikon yang LANGSUNG terlihat & bisa disentuh
di posisi pojok kanan atas kartu (pensil = Edit, tempat sampah = Hapus)
tanpa perlu buka menu dulu.

## 3. Rekap: kolom "Kelas" setelah Nama Siswa
Tabel "Absensi Siswa" di halaman Rekap sekarang punya kolom Kelas persis
setelah kolom Nama Siswa (NIS → Nama Siswa → **Kelas** → tanggal 1-31 →
S/I/A/Jml). Tabel "Absensi Pembina" tidak berubah (pembina tidak punya
kelas).

## File yang diubah
- `resources/views/ekstrakurikuler/index.blade.php`
- `app/Http/Controllers/EkskulRekapController.php` — data siswa kini
  menyertakan `kelas`.
- `resources/views/ekstrakurikuler/rekap-bulanan.blade.php` — kolom Kelas
  ditambahkan di tabel siswa.

**Tidak perlu migrasi baru.**

## Cara menerapkan
1. Timpa ketiga file di atas ke `C:\laragon\www\sim-spenga`.
2. Tidak perlu migrasi, tidak perlu `npm run build`.
3. Test: cek tampilan kartu kegiatan (jarak antar kotak & tombol Edit/Hapus
   langsung terlihat tanpa buka menu), lalu buka Rekap kegiatan yang sudah
   ada anggotanya → pastikan kolom Kelas muncul dan datanya benar.
