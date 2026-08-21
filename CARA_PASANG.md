# Perbaikan — Kenaikan Kelas: "Siswa Tidak Muncul"

## Penyebab

Halaman Kenaikan Kelas sebelumnya MEMAKSA "Kelas Asal" selalu diambil
dari Tahun Ajaran yang SEDANG AKTIF. Ini hanya benar kalau admin
menjalankan Kenaikan Kelas SEBELUM mengaktifkan tahun ajaran baru.

Begitu tahun ajaran baru (mis. 2026/2027) sudah lebih dulu diaktifkan,
"Kelas Asal" otomatis menunjuk ke kelas-kelas 2026/2027 yang BARU
dibuat dan masih KOSONG — padahal siswa Anda kemungkinan masih
tercatat di kelas tahun ajaran SEBELUMNYA. Makanya begitu kelas asal
dipilih, daftar siswanya kosong.

## Perbaikan

Sekarang admin **memilih sendiri** "Tahun Ajaran Asal" lewat dropdown
di halaman Kenaikan Kelas (defaultnya ditebak otomatis — tahun SEBELUM
periode aktif kalau ada, atau periode aktif itu sendiri kalau tidak).
Tahun Ajaran Tujuan tetap dihitung OTOMATIS dari Tahun Ajaran Asal
yang dipilih (tidak berubah dari desain STEP 4 — admin tidak bisa
asal pilih tujuan sembarangan), jadi fitur ini sekarang benar
berapa pun urutan aktivasi yang sudah Anda lakukan.

## Isi paket (3 file, semua TIMPA)

```
app/Models/TahunAjaran.php
app/Http/Controllers/KenaikanKelasController.php
resources/views/kenaikan-kelas/index.blade.php
```

Tidak ada migrasi baru, tidak ada route baru — cukup timpa 3 file
ini, tidak perlu `php artisan migrate`.

## Cara pasang

1. Timpa 3 file di atas.
2. Buka halaman Kenaikan Kelas.
3. Di dropdown **"Tahun Ajaran Asal"**, coba pilih tahun-tahun yang
   ada satu per satu sampai Anda menemukan yang menampilkan siswa
   di "Kelas Asal" — itulah tahun ajaran tempat siswa Anda sekarang
   berada.
4. Lanjutkan proses kenaikan kelas seperti biasa dari situ.

## Catatan

Setelah menemukan tahun ajaran yang benar dan sekali berhasil
memproses kenaikan kelas, sistem akan otomatis mengarahkan kembali
ke Tahun Ajaran Asal yang sama (lewat query string), jadi Anda tidak
perlu memilih ulang setiap kali berpindah kelas asal berikutnya.
