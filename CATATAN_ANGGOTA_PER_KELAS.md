# Revisi: Menu Anggota Ekskul jadi checklist per kelas

## Apa yang berubah
Sebelumnya menambah anggota hanya lewat cari nama/NIS satu-satu. Sekarang
ada cara baru yang jadi cara utama:

1. **Pilih Kelas** — dropdown daftar kelas aktif.
2. **Checklist siswa kelas itu** — semua siswa di kelas tersebut tampil
   dengan checkbox. Yang SUDAH jadi anggota otomatis tercentang.
3. **Tombol "1 Kelas Ikut Semua"** — mencentang semua siswa di kelas yang
   sedang ditampilkan sekaligus.
4. **Tombol "Batal"** — reload halaman tanpa menyimpan apa pun, kalau ada
   centang yang salah klik sebelum sempat disimpan.
5. **Sekaligus jadi menu Edit** — checklist ini BUKAN cuma untuk menambah:
   kalau ada siswa yang terlanjur salah masuk, tinggal buka lagi kelasnya,
   HAPUS centangnya, lalu Simpan — otomatis dikeluarkan. Tidak perlu menu
   edit terpisah, checklist yang sama dipakai untuk menambah maupun
   mengoreksi.

Fitur lama (cari individual lintas kelas) tetap ada di bawahnya, untuk
kasus tambah 1-2 siswa saja tanpa buka checklist kelas. Daftar anggota
+ tombol "Keluarkan" per orang di bagian bawah juga tetap ada sebagai
cara koreksi alternatif.

**Penting soal cara kerja Simpan**: saat Simpan ditekan, sistem
menyinkronkan HANYA siswa dari kelas yang sedang ditampilkan — siswa dari
kelas lain yang sudah jadi anggota kegiatan ini tidak ikut tersentuh sama
sekali, aman untuk dipakai kelas demi kelas.

## File yang diubah
- `app/Http/Controllers/EkstrakurikulerAnggotaController.php` — method baru
  `syncKelas()` untuk sinkronisasi checklist per kelas.
- `resources/views/ekstrakurikuler/anggota.blade.php` — UI checklist per
  kelas + tombol Centang Semua/Batal, form cari individual dipindah jadi
  bagian kedua.
- `routes/web.php` — route baru
  `POST ekstrakurikuler/{ekstrakurikuler}/anggota/sync-kelas`.

**Tidak perlu migrasi baru.**

## Cara menerapkan (di Laragon)
1. Salin ke `C:\laragon\www\sim-spenga` (timpa yang lama):
   - `app/Http/Controllers/EkstrakurikulerAnggotaController.php`
   - `resources/views/ekstrakurikuler/anggota.blade.php`
   - `routes/web.php`
2. Tidak perlu migrasi, tidak perlu `npm run build`.
3. Test: buka Anggota kegiatan Pramuka → pilih kelas 7A → klik "1 Kelas
   Ikut Semua" → Simpan → pastikan semua siswa 7A masuk daftar anggota.
   Buka lagi kelas 7A → hapus centang 1 siswa → Simpan → pastikan siswa
   itu hilang dari daftar anggota, sisanya tetap. Coba juga tombol "Batal"
   (centang beberapa lalu klik Batal, pastikan tidak ada yang tersimpan).
