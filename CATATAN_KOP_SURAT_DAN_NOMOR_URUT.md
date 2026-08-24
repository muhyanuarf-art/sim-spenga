# Fitur baru: KOP Surat otomatis + kolom "No" di semua laporan cetak

## 1. KOP Surat
Komponen baru `<x-kop-surat />` — menampilkan Nama Sekolah (besar, huruf
kapital) dan "Kabupaten/Kota, Provinsi" di bawahnya, dengan garis pembatas
ganda ala kop surat resmi. Datanya otomatis dari Pengaturan Sekolah
(`$pengaturanSekolahGlobal`, sudah di-share ke semua halaman) — tidak
perlu diisi ulang di mana pun.

Kalau Nama Sekolah belum diisi di Pengaturan Sekolah, muncul pengingat
kecil (hanya di layar, TIDAK ikut kecetak) yang mengarahkan ke halaman
Pengaturan Sekolah untuk melengkapinya.

Sudah dipasang di bagian PALING ATAS bagian yang dicetak (`print-section`)
pada **9 halaman**:
1. Rekap Absensi Kelas (Wali Kelas)
2. Jurnal Kelas (Wali Kelas)
3. Rekapitulasi — Rekap Guru (tabel pertama)
4. Rekapitulasi — Rekap Kelas (tabel kedua)
5. Pemanggilan Orang Tua (BK)
6. Pembinaan (BK)
7. Kasus Pelanggaran (BK)
8. Jurnal Mengajar (Laporan Guru)
9. Absensi per Mata Pelajaran (Laporan Guru)
10. Rekap Absensi Ekstrakurikuler

## 2. Kolom "No" (nomor urut)
Ditambahkan ke laporan yang sebelumnya belum punya:
- Rekap Absensi Kelas
- Jurnal Kelas
- Rekapitulasi Guru & Rekapitulasi Kelas (2 tabel)
- Pemanggilan Orang Tua
- Pembinaan
- Jurnal Mengajar (Laporan Guru)
- Absensi per Mata Pelajaran (Laporan Guru)

Kasus Pelanggaran & Rekap Absensi Ekstrakurikuler TIDAK diubah — keduanya
sudah punya kolom No sebelumnya.

## ⚠️ WAJIB: jalankan `npm run build`
Beda dari beberapa perbaikan sebelumnya — kali ini KOP Surat memakai
beberapa class Tailwind yang **belum pernah dipakai di mana pun** di
aplikasi ini sebelumnya (`border-b-4`, `border-double`, `border-slate-800`,
`text-slate-900`, `sm:text-xl`). Karena project ini pakai Vite (bukan
Tailwind versi CDN), class yang belum pernah dipakai TIDAK akan ikut
ter-compile ke CSS sampai build dijalankan ulang — kalau dilewati, KOP
Surat akan tampil tanpa gaya (nama sekolah polos tanpa garis pembatas,
dst).

## File yang ditambah/diubah
- `resources/views/components/kop-surat.blade.php` **(baru)**
- `resources/views/walikelas/absensi-bulanan.blade.php`
- `resources/views/walikelas/jurnal-kelas.blade.php`
- `resources/views/rekap/index.blade.php`
- `resources/views/bk/pemanggilan/index.blade.php`
- `resources/views/bk/pembinaan/index.blade.php`
- `resources/views/bk/kasus/index.blade.php` (cuma tambah KOP Surat, kolom No sudah ada sebelumnya)
- `resources/views/laporan/jurnal-guru.blade.php`
- `resources/views/laporan/absensi-guru.blade.php`
- `resources/views/ekstrakurikuler/rekap-bulanan.blade.php` (cuma tambah KOP Surat, kolom No sudah ada sebelumnya)

**Tidak ada migrasi baru.**

## Cara menerapkan (di Laragon)
1. Salin semua file di atas ke lokasi yang sama persis di
   `C:\laragon\www\sim-spenga` (timpa semua).
2. **WAJIB** jalankan build:
   ```powershell
   cd C:\laragon\www\sim-spenga
   npm run build
   ```
3. Tidak perlu migrasi.
4. Test tiap 1 halaman dari 9 di atas: pastikan KOP Surat muncul rapi di
   bagian paling atas hasil Cetak/Export PDF (dengan garis pembatas ganda,
   bukan tampil polos tanpa gaya — kalau masih polos berarti build belum
   kepakai, coba hard refresh `Ctrl+Shift+R`), dan kolom "No" terisi urut
   1, 2, 3, ... sesuai jumlah baris data.
5. Kalau Nama Sekolah di Pengaturan Sekolah belum diisi, isi dulu supaya
   KOP Surat bisa tampil (kalau kosong, yang muncul cuma pengingat kecil,
   bukan KOP Surat-nya).
