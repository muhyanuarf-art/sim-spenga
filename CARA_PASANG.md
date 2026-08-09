# Menu Baru: Status WhatsApp Ortu (dengan filter bulan)

## Apa yang dibuat

Menu **"📲 Status WhatsApp Ortu"** — histori pengiriman notifikasi
Alfa ke orang tua, dengan filter Bulan & Tahun (mirip Rekapitulasi).
Muncul di sidebar bagian **Laporan**.

## Siapa yang bisa lihat apa

| Role | Cakupan data |
|---|---|
| Admin, Kurikulum, Kepala Sekolah | Semua siswa/kelas, + bisa filter per kelas |
| Guru yang jadi **Wali Kelas** | Hanya siswa di kelas walinya sendiri |
| Guru mapel biasa (bukan wali kelas) | Menu tetap muncul, tapi ditampilkan pesan penjelasan (bukan tabel kosong) — karena notifikasi ini levelnya per KELAS/hari, bukan per mapel |

## Isi halamannya

- 3 kartu ringkasan: **Terkirim**, **Menunggu Diproses**, **Gagal Terkirim**
- Peringatan otomatis kalau ada yang "Menunggu" (kemungkinan queue
  worker belum jalan) atau "Gagal" (kemungkinan nomor WA salah/kosong)
- Tabel histori: Tanggal, Nama Siswa, Kelas, Mapel & jam penentu,
  Status, Waktu Terkirim

## File yang ditambah/diubah

| File | Keterangan |
|---|---|
| `database/migrations/..._add_mapel_jam_to_notifikasi_alfa_terkirims_table.php` | + kolom `mata_pelajaran_id`, `jam_ke` di tabel pelacak notifikasi |
| `app/Models/NotifikasiAlfaTerkirim.php` | + relasi `mapel()` |
| `app/Http/Controllers/NotifikasiWhatsappController.php` | Controller baru untuk halaman ini |
| `app/Http/Controllers/MengajarController.php` | Simpan mapel & jam saat notifikasi dibuat (supaya histori bisa tampilkan info itu) |
| `resources/views/notifikasi-wa/index.blade.php` | View halaman baru |
| `resources/views/layouts/app.blade.php` | + link menu di sidebar |
| `routes/web.php` | + route `notifikasi-wa.index` |

## Cara pasang

1. Salin semua file di atas ke project Anda (timpa yang lama).
2. Jalankan migration:
   ```bash
   php artisan migrate
   ```
3. Clear cache:
   ```bash
   php artisan route:clear
   php artisan view:clear
   ```
4. Login dengan berbagai role untuk cek:
   - Admin/Kurikulum/Kepala Sekolah → menu muncul, ada filter Kelas,
     data lengkap se-sekolah.
   - Guru Wali Kelas → data hanya kelasnya sendiri, tanpa filter Kelas.
   - Guru mapel biasa (bukan wali) → menu tetap ada, tapi muncul
     pesan "hanya relevan untuk Wali Kelas".
   - Ganti bulan di filter → data ikut berubah sesuai bulan yang dipilih.

**Catatan:** fitur ini menampilkan histori dari data yang SUDAH ada
di tabel `notifikasi_alfa_terkirims` — karena Anda belum menjalankan
fitur notifikasi WA di server (masih menunggu pindah ke VPS/hosting),
untuk saat ini tabelnya akan kosong. Begitu fitur WA-nya aktif nanti
(sesuai panduan sebelumnya), histori akan otomatis terisi dan bisa
dipantau lewat menu ini.
