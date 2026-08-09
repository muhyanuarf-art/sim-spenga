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

---

# Update: Perbaikan supaya bisa jalan di localhost + retry maks 2x

## Kabar baik soal localhost

**Fitur kirim WA Fonnte ini SUDAH BISA jalan penuh di localhost/Laragon**,
tidak perlu tunggu pindah ke hosting/VPS dulu. Alasannya: Laravel yang
memanggil KELUAR ke server Fonnte (`Http::post(...)`) — bukan Fonnte yang
memanggil MASUK ke aplikasi Anda. Selama komputer Anda tersambung
internet, request kirim pesan akan sampai ke Fonnte dan pesan akan
terkirim ke WhatsApp orang tua meski Laravel-nya jalan di `localhost`.

(Yang BENAR-BENAR butuh domain publik itu fitur *webhook* — mis. status
"dibaca"/"delivered" real-time dari Fonnte ke aplikasi Anda. Fitur itu
belum dipakai di sini, jadi tidak masalah untuk sekarang.)

## Bug yang diperbaiki

Kode kirim WA sebelumnya cuma mengecek **kode HTTP** response Fonnte
untuk menentukan sukses/gagal. Padahal Fonnte **sering balas HTTP 200
meskipun pesan sebenarnya GAGAL** (misalnya nomor tidak valid) — jadi ada
risiko notifikasi ditandai "Terkirim" padahal sebenarnya tidak terkirim.
Sekarang kode mengecek field `status` di body JSON respons Fonnte, bukan
cuma kode HTTP-nya saja.

## Retry maks 2x ditambahkan

Sesuai aturan sekolah, sekarang ada 2 kolom baru di tabel
`notifikasi_alfa_terkirims`: `percobaan_ke` dan `keterangan_gagal`.

- Kalau Fonnte bilang gagal karena **nomor bermasalah** ("target
  invalid" dsb): dicoba lagi otomatis 1x (jeda 2 menit), maksimal
  **2x percobaan total**. Kalau percobaan ke-2 masih gagal juga →
  berhenti permanen, status jadi "Gagal", keterangan mencatat kemungkinan
  nomor bukan WhatsApp aktif.
- Kalau gagalnya karena **gangguan teknis** (device Fonnte
  terputus/timeout dsb): ditangani terpisah oleh mekanisme retry job
  bawaan Laravel (otomatis dicoba lagi sampai 3x dengan jeda 15
  detik/1 menit/5 menit), tidak ikut menghitung "percobaan" versi sekolah.

## Pembersihan

- Menghapus semua file implementasi WA versi lama (Meta WhatsApp Cloud
  API) yang sempat tercampur di repo tapi sudah tidak dipakai:
  `NotifikasiWa`, `KirimNotifikasiAlfaJob`, `WhatsAppWebhookController`,
  `NotifikasiAlfaDispatcher`, `WhatsAppCloudService`, migration
  `notifikasi_was`/`no_hp_ortu`, view `walikelas/status-whatsapp.blade.php`,
  dan file `fitur-notifikasi-wa-alfa.patch` yang sempat ikut ter-commit.
- `storage/logs/*.log` dan `storage/framework/**` (cache view/session)
  di-untrack dari Git dan ditambahkan ke `.gitignore` — file-file ini
  seharusnya tidak ikut di-commit karena berubah tiap kali aplikasi
  jalan, bukan bagian dari kode sumber.

## Langkah pasang di lokal (Laragon)

```powershell
php artisan migrate
php artisan queue:work
```

Isi `.env`:
```
QUEUE_CONNECTION=database
FONNTE_TOKEN=isi_token_device_fonnte_anda
FONNTE_URL=https://api.fonnte.com/send
```

Jalankan `php artisan queue:work` di terminal terpisah (biarkan
berjalan) setiap kali Anda mau menguji fitur ini — kalau tidak jalan,
notifikasi akan menumpuk di status "Menunggu" selamanya.

