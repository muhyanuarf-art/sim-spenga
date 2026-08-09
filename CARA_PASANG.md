# Notifikasi WA Otomatis ke Orang Tua (Siswa Alfa) + Bugfix Dashboard Guru

## Bug #2 (sudah diperbaiki)
Kartu jadwal yang **belum diisi** di dashboard guru kadang ikut
berwarna hijau — ternyata warna per-mapel dipilih otomatis dari
daftar warna yang termasuk hijau. Hijau sekarang dihapus dari daftar
itu, dikhususkan untuk status **"Terisi"** saja.

---

## Fitur #1: Notifikasi WA Otomatis

### Kenapa pakai Queue (bukan kirim langsung)?

Memanggil API WhatsApp itu "menunggu jaringan luar" — bisa 1 sampai
beberapa detik, kadang lebih lama kalau providernya lambat. Kalau
dipanggil LANGSUNG saat guru klik simpan, guru ikut menunggu itu,
dan kalau ada BANYAK siswa Alfa sekaligus (mis. akhir bulan banyak
yang bolos), prosesnya makin lama — bisa bikin website berasa lemot.

**Solusi:** guru simpan absensi → sistem cuma menuliskan 1 baris kecil
ke tabel `notifikasi_alfa_terkirims` (sangat cepat, <10ms) → job
pengiriman WA yang sesungguhnya diproses **terpisah** oleh
`php artisan queue:work` di background, kapan saja dia sempat.
Guru tidak pernah menunggu proses WA sama sekali.

Dengan skala sekolah Anda (471 siswa, ±8 jam KBM/hari), jumlah pesan
yang dikirim per hari **realistisnya cuma puluhan** (jumlah siswa
Alfa hari itu, bukan 471), jadi driver antrian paling sederhana
(`database`, tanpa perlu Redis) **sudah lebih dari cukup** — tidak
akan membebani server.

### Aturan pengiriman (anti-spam)

- Notifikasi dikirim berdasarkan **Absensi Kelas** (status dari guru
  mapel jam paling akhir), bukan dari sesi mapel manapun — jadi kalau
  siswa Alfa di jam 1 tapi ternyata dikoreksi Hadir oleh guru jam 2,
  **tidak jadi dikirim** notifikasi (karena status akhirnya bukan Alfa).
- **1 siswa maksimal 1x notifikasi per hari**, walau guru mengedit
  ulang absensinya berkali-kali hari itu.
- Kalau nomor WA ortu kosong, siswa itu otomatis dilewati (tidak error).
- ⚠️ **Keterbatasan yang disengaja**: kalau status Alfa sudah terlanjur
  terkirim, lalu guru LAIN dengan jam lebih akhir mengoreksi jadi
  Hadir di hari yang sama, sistem TIDAK mengirim pesan
  "koreksi/pembatalan". Ini disederhanakan supaya fitur tetap ringan.
  Bisa dikembangkan lagi kalau nanti dibutuhkan.

### File yang ditambah/diubah

| File | Keterangan |
|---|---|
| `database/migrations/..._add_wa_ortu_to_siswas_table.php` | + kolom `nama_ortu`, `no_wa_ortu` di tabel siswa |
| `database/migrations/..._create_notifikasi_alfa_terkirims_table.php` | Tabel anti-duplikat (1 siswa 1x/hari) |
| `app/Models/Siswa.php` | + `nama_ortu`, `no_wa_ortu` ke fillable |
| `app/Models/NotifikasiAlfaTerkirim.php` | Model baru |
| `app/Jobs/KirimNotifikasiAlfaWhatsapp.php` | **Job antrian** — logic kirim WA (pakai contoh gateway Fonnte) |
| `app/Http/Controllers/MengajarController.php` | Setelah simpan absensi, cek & antrikan notifikasi (query ringan saja) |
| `app/Http/Controllers/SiswaController.php` | Validasi field baru di form tambah/edit siswa |
| `app/Imports/SiswaImport.php` | Import Excel dukung kolom `nama_ortu`, `no_wa_ortu` |
| `config/services.php` | Config token & URL gateway WA (Fonnte) |
| `.env.example` | **`QUEUE_CONNECTION` diubah dari `sync` ke `database`** (lihat catatan penting di bawah), + `FONNTE_TOKEN`/`FONNTE_URL` |
| `resources/views/siswa/index.blade.php` | Form & tabel Data Siswa: field Nama/No. WA Ortu |
| `resources/views/dashboard/guru.blade.php` | Bugfix #2 (hapus emerald dari palet warna mapel) |

### ⚠️ SANGAT PENTING: cek `.env` Anda

File `.env` project Anda (bukan `.env.example`) kemungkinan besar
masih punya baris:
```
QUEUE_CONNECTION=sync
```
Kalau dibiarkan `sync`, job WA akan **tetap berjalan langsung** di
request guru (persis yang ingin kita hindari)! Ubah jadi:
```
QUEUE_CONNECTION=database
```
Tabel `jobs` untuk antrian ini **sudah otomatis ada** dari migration
bawaan Laravel di project Anda — tidak perlu bikin tabel baru untuk itu.

### Cara pasang

1. Salin semua file di atas ke project Anda (timpa yang lama).
2. Jalankan migration:
   ```bash
   php artisan migrate
   ```
3. Edit `.env` Anda:
   ```
   QUEUE_CONNECTION=database
   FONNTE_TOKEN=isi_token_dari_fonnte.com
   ```
   (Daftar & hubungkan nomor WA sekolah di https://fonnte.com dulu
   untuk dapat token. Kalau nanti pakai provider lain seperti Wablas,
   tinggal sesuaikan bagian `Http::...->post(...)` di
   `app/Jobs/KirimNotifikasiAlfaWhatsapp.php` sesuai dokumentasi API
   provider tsb — strukturnya sudah disiapkan supaya mudah diganti.)
4. Clear cache:
   ```bash
   php artisan config:clear
   ```
5. **Jalankan queue worker** — ini WAJIB, kalau tidak dijalankan,
   job akan menumpuk di tabel `jobs` dan pesan WA **tidak akan pernah
   terkirim**. Ada 2 cara tergantung jenis hosting Anda:

   **A. Kalau punya akses SSH & bisa jalankan proses background (VPS)**
   Gunakan Supervisor supaya worker otomatis nyala lagi kalau
   servernya restart:
   ```ini
   # /etc/supervisor/conf.d/sim-spenga-worker.conf
   [program:sim-spenga-worker]
   process_name=%(program_name)s_%(process_num)02d
   command=php /path/ke/project/artisan queue:work --queue=notifikasi,default --sleep=3 --tries=3
   autostart=true
   autorestart=true
   numprocs=1
   user=www-data
   redirect_stderr=true
   stdout_logfile=/path/ke/project/storage/logs/worker.log
   ```
   Lalu:
   ```bash
   sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start sim-spenga-worker:*
   ```

   **B. Kalau hosting shared/cPanel (tidak bisa proses background terus-menerus)**
   Pakai **Cron Job** yang jalan tiap menit, ambil job yang ada,
   proses, lalu berhenti sendiri (lebih ramah untuk shared hosting):
   ```bash
   * * * * * cd /path/ke/project && php artisan queue:work --stop-when-empty --max-time=55 >> /dev/null 2>&1
   ```
   Tambahkan lewat menu **Cron Jobs** di cPanel.

6. Isi nomor WA orang tua:
   - Manual: menu **Data Siswa** → Edit/Tambah → isi "Nama Orang
     Tua/Wali" & "No. WhatsApp Ortu".
   - Massal (untuk 471 siswa): menu **Data Siswa** → **Import Excel**
     → download template (sudah ada kolom `nama_ortu` & `no_wa_ortu`)
     → isi → upload lagi.

### Cara test

1. Pastikan queue worker sudah jalan (cara A atau B di atas).
2. Isi nomor WA ortu Anda sendiri di 1 siswa test.
3. Isi absensi siswa itu dengan status **Alfa**, simpan.
4. Cek tabel `notifikasi_alfa_terkirims` — harus muncul baris baru
   `status_kirim = pending`, lalu berubah jadi `terkirim` setelah
   worker memprosesnya (beberapa detik kalau pakai Supervisor, atau
   maks. 1 menit kalau pakai cron).
5. Cek WhatsApp nomor test tsb — pesan harus masuk.
6. Kalau worker BELUM jalan, `status_kirim` akan diam di `pending`
   selamanya — itu tandanya langkah 5 (jalankan worker) belum benar.
