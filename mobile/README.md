# Aplikasi Android SIM-SPENGA

Portal masuk untuk **guru, guru BK, kurikulum, kesiswaan, kepala sekolah, dan TU**.
Akun **Admin** dan **portal orang tua** sengaja tidak dapat masuk lewat aplikasi ini —
penolakannya dilakukan di server, bukan sekadar disembunyikan di aplikasi.

---

## Cara kerjanya

1. Layar masuk **native** mengirim email/NIP + kata sandi ke `POST /aplikasi/masuk`.
2. Server memeriksa kredensial, keaktifan akun, dan **perannya**.
3. Bila lolos, server membalas sebuah **tautan masuk-sekali** yang ditandatangani
   dan berumur 60 detik.
4. Aplikasi membuka tautan itu di WebView → sesi web terbentuk → seluruh aplikasi
   SIM-SPENGA tampil di dalam aplikasi Android.

**Kata sandi tidak pernah disimpan di ponsel.** Yang tersimpan hanya alamat server
dan (bila dicentang) alamat surel, supaya tidak perlu diketik ulang tiap hari.

Karena isinya aplikasi web yang sama, **setiap perbaikan di server langsung sampai
ke ponsel** tanpa memasang ulang aplikasi.

---

## Langkah membuat APK

Berkas Dart di folder ini adalah **isi** aplikasinya. Kerangka proyek Flutter
(folder `android/`, `ios/`, dan berkas Gradle) dibuat oleh perintah `flutter create`
supaya versinya cocok dengan Flutter SDK di komputer Anda.

> **Kerangka Android-nya sudah dibuat dan APK-nya sudah pernah dibangun.**
> Folder `android/`, izin internet, nama aplikasi, dan izin `http` sudah terpasang.
> Yang tersisa hanya langkah 1 di bawah.

### 1. Isi alamat server bawaan

Buka `lib/layanan.dart`, ubah baris:

```dart
static const String alamatBawaan = 'http://192.168.1.10';
```

menjadi alamat komputer server sekolah. Pengguna tetap bisa menggantinya dari
dalam aplikasi lewat tombol **Server:** di bawah tombol Masuk.

### 2. Jalankan atau bangun APK

```bash
flutter run                 # uji di ponsel yang tersambung
flutter build apk --release # hasilkan APK
```

APK-nya ada di `build/app/outputs/flutter-apk/app-release.apk`.

---

## Tiga hal yang harus disiapkan di server

Tanpa ini, aplikasi akan gagal masuk meski kodenya benar.

### 1. Alamat yang bisa dijangkau ponsel

`APP_URL` sekarang `http://sim-spenga.test` — nama Laragon yang **hanya dikenali
di komputer itu sendiri**. Ponsel tidak bisa menjangkaunya.

Untuk uji coba di wifi sekolah, ubah `.env`:

```
APP_URL=http://192.168.1.10
```

Ganti dengan IP LAN komputer server (lihat dengan `ipconfig`). Lalu:

```bash
php artisan config:clear
```

### 2. Lisensi harus aktif untuk alamat itu

**Ini penghadang yang paling sering terlewat.** Aktivasi terikat alamat server.
Bila ponsel menghubungi lewat IP yang berbeda dari alamat saat aktivasi, seluruh
permintaan — termasuk `/aplikasi/masuk` — akan ditolak.

Dua pilihan:

- **Aktifkan ulang** lewat peramban di alamat baru itu (`http://192.168.1.10/aktivasi`), atau
- Longgarkan di `.env`: `LISENSI_TERIKAT_HOST=false`, lalu `php artisan config:clear`.

### 3. Server harus bisa diakses dari jaringan

Di Laragon, secara bawaan Apache hanya melayani `localhost`. Pastikan Windows
Firewall mengizinkan port 80, dan ponsel berada di **wifi yang sama**.

---

## Bila gagal masuk — arti pesannya

| Pesan di aplikasi | Sebabnya | Tindakan |
|---|---|---|
| Tidak bisa menghubungi server di … | Ponsel tidak menjangkau server | Periksa wifi, alamat server, dan firewall |
| Email/NIP atau kata sandi salah | Kredensial keliru | Coba lagi; sengaja tidak dibedakan agar tidak membocorkan akun mana yang ada |
| Akun Admin tidak dapat masuk lewat aplikasi Android | Peran ditolak | Gunakan peramban di komputer |
| Akun Anda dinonaktifkan | `is_active = false` | Hubungi Admin |
| Aplikasi di server belum diaktifkan untuk alamat ini | Lisensi terikat host lain | Lihat bagian 2 di atas |
| Sesi Anda sudah berakhir | Sesi web kedaluwarsa | Masuk lagi — ini normal |

---

## Berkas di folder ini

| Berkas | Isinya |
|---|---|
| `lib/main.dart` | Titik awal aplikasi & tema (huruf & tombol sengaja lebih besar) |
| `lib/layar_masuk.dart` | Layar masuk native + pengaturan alamat server |
| `lib/layar_web.dart` | WebView aplikasi, tombol keluar, penanganan sesi habis |
| `lib/layanan.dart` | Satu-satunya penghubung ke server |
| `pubspec.yaml` | Daftar paket |

Sisi servernya ada di `app/Http/Controllers/AplikasiMobileController.php`.
