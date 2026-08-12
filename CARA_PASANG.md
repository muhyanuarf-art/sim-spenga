# Perbaikan Form "Catat Kasus / Pelanggaran Baru"

## Perubahan

1. **Kategori & Poin tidak bisa diisi manual** — sekarang tampil sebagai
   kotak abu-abu read-only (bukan input), otomatis terisi begitu BK
   memilih Jenis Pelanggaran dari master.
   - **Diamankan sampai level server**: form bahkan tidak lagi mengirim
     nilai Kategori/Poin ke server sama sekali. Server SELALU mengambil
     ulang kategori & poin dari tabel master (`jenis_pelanggarans`)
     berdasarkan `jenis_pelanggaran_id` yang dipilih — jadi walau ada
     yang coba mengubah lewat DevTools/request manual, nilai yang
     tersimpan tetap yang benar dari master, bukan hasil rekayasa.
   - Konsekuensinya: **Jenis Pelanggaran sekarang wajib dipilih** dari
     master (sebelumnya opsional/bisa isi manual). Kalau jenisnya belum
     ada di master, ada link cepat ke halaman **Data Pelanggaran (Master)**.

2. **Kronologi wajib diisi** — sudah divalidasi wajib (`required`)
   sebelumnya, sekarang ditambah minimal 10 karakter (`minlength`) supaya
   tidak diisi asal-asalan (misal cuma 1 huruf).

3. **Upload Bukti (Foto/PDF)** — field baru, opsional (boleh
   dikosongkan), menerima JPG/PNG/PDF maksimal 5MB. Kalau diisi, file
   tersimpan dan muncul sebagai link "📎 Lihat Bukti" di riwayat/timeline
   profil siswa.

## File yang diubah

| File | Keterangan |
|---|---|
| `database/migrations/..._add_bukti_file_to_kasus_siswas_table.php` | + kolom `bukti_file` |
| `app/Models/KasusSiswa.php` | + `bukti_file` di fillable, + accessor URL |
| `app/Http/Controllers/BkKasusController.php` | Kategori/poin diambil dari master (bukan input), validasi file upload |
| `resources/views/bk/kasus/create.blade.php` | Form halaman mandiri diperbarui |
| `resources/views/bk/siswa/show.blade.php` | Modal "Catat Pelanggaran" + tampilan link bukti di timeline |

## Cara pasang

1. Salin semua file di atas ke project Anda (timpa yang lama).
2. Jalankan migration:
   ```bash
   php artisan migrate
   ```
3. **WAJIB** — buat symlink storage supaya file upload bisa diakses
   browser (fitur upload file pertama di aplikasi ini, jadi kemungkinan
   besar belum pernah dijalankan sebelumnya):
   ```bash
   php artisan storage:link
   ```
4. Clear cache:
   ```bash
   php artisan view:clear
   ```
5. Test:
   - Buka Catat Kasus (dari menu Kasus/Pelanggaran atau dari profil
     siswa) → pilih Jenis Pelanggaran → Kategori & Poin harus otomatis
     terisi dan **tidak bisa diklik/diedit**.
   - Coba submit dengan Kronologi kosong atau cuma beberapa huruf →
     harus ditolak.
   - Upload foto/PDF sebagai bukti → simpan → cek di timeline profil
     siswa, harus muncul link "📎 Lihat Bukti" yang bisa dibuka.
   - Coba submit **tanpa** upload bukti → harus tetap berhasil (opsional).
