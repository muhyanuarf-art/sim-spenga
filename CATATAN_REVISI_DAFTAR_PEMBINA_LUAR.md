# Revisi: Pembina Luar Sekolah jadi daftar dengan tombol Input/Batal

## Apa yang berubah
Kotak "Pembina dari Luar Sekolah" yang sebelumnya textarea (ketik bebas
1 nama per baris) sekarang jadi:
- **Daftar** nama yang sudah ditambahkan (dengan tombol "x" per baris untuk hapus).
- Tombol **"+ Tambah Pembina Luar Sekolah"** yang membuka form kecil (Nama +
  Kontak).
- Di form kecil itu ada tombol **"Input"** (menambahkan ke daftar & menutup
  form kecil) dan **"Batal"** (menutup form kecil tanpa menambahkan apa pun).

Berlaku di form Tambah Kegiatan maupun form Edit tiap baris (data pembina
luar sekolah yang sudah tersimpan otomatis muncul sebagai daftar awal saat
Edit dibuka).

Secara teknis: daftar ini dikelola di sisi browser (Alpine.js), lalu saat
"Simpan" ditekan, seluruh daftar dikirim ke server sebagai 2 array sejajar
(nama & kontak) — bukan lagi teks bebas yang di-parsing per baris di server.

## Catatan penting untuk fitur Absensi Ekskul (belum dibangun)
Anda sebutkan aturan bisnisnya, saya catat supaya konsisten nanti:
- Yang **mengabsen** (mengisi form absensi): hanya **pembina dari sekolah**
  (user sistem), bukan pembina luar sekolah.
- Yang **diabsen** (dicatat kehadirannya): **siswa** + **pembina dari
  sekolah** + **pembina dari luar sekolah**, semuanya.

Ini belum berpengaruh ke paket kali ini (baru soal input master data), tapi
akan jadi acuan desain tabel absensi ekskul & hak akses menu Absensi nanti.

## File yang diubah
- `app/Http/Controllers/EkstrakurikulerController.php` — validasi & simpan
  pembina eksternal sekarang menerima `pembina_eksternal_nama[]` +
  `pembina_eksternal_kontak[]` (2 array sejajar), bukan `pembina_eksternal`
  (1 string textarea).
- `resources/views/ekstrakurikuler/index.blade.php` — UI daftar +
  Input/Batal untuk pembina luar sekolah, di form Tambah maupun tiap
  form Edit.

**Tidak perlu migrasi baru** (struktur tabel `ekstrakurikuler_pembinas`
dari paket sebelumnya tidak berubah, cuma cara mengisinya dari form yang
berubah).

## Cara menerapkan (di Laragon)
1. Salin ke `C:\laragon\www\sim-spenga` (timpa yang lama):
   - `app/Http/Controllers/EkstrakurikulerController.php`
   - `resources/views/ekstrakurikuler/index.blade.php`
2. Tidak perlu migrasi, tidak perlu `npm run build`.
3. Test: buka "+ Tambah Kegiatan" → klik "+ Tambah Pembina Luar Sekolah" →
   isi nama (+ kontak opsional) → klik "Input" → pastikan muncul di daftar
   dan form kecil tertutup. Coba juga klik "Batal" (form kecil tertutup,
   TIDAK menambah apa pun ke daftar). Simpan kegiatan, lalu buka Edit →
   pastikan daftar pembina luar sekolah yang tadi diisi muncul kembali,
   dan tombol "x" di tiap baris berhasil menghapusnya.
