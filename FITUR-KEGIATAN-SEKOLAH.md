# Fitur Baru: Kegiatan Sekolah di Luar Jam KBM

Untuk hari-hari sekolah yang tidak berisi KBM: lomba Agustus, tryout &
Asesmen Sumatif (Tengah/Akhir Semester), classmeeting, pesantren Ramadan,
dan kegiatan lain.

---

## 1. Masalah yang diselesaikan

Absensi siswa sebelumnya **hanya** bisa masuk lewat jadwal mengajar — guru
mapel, per jam pelajaran. Pada hari kegiatan tidak ada guru mapel yang
mengisi, sehingga:

* kehadiran siswa hari itu tidak tercatat sama sekali;
* rekap bulanan berlubang pada tanggal-tanggal kegiatan;
* notifikasi WhatsApp Alfa ke orang tua tidak pernah jalan justru pada hari
  yang paling rawan siswa tidak datang.

Sekarang kegiatan dijadwalkan lebih dulu, lalu **Wali Kelas** mengisi
absensinya, dan **notifikasi WhatsApp Alfa tetap berjalan otomatis** lewat
jalur yang sama persis dengan absensi KBM.

---

## 2. Pembagian hak akses (sengaja dibuat tegas)

| Peran | Boleh |
|---|---|
| Kesiswaan, Kurikulum, Admin | Menjadwalkan, mengubah, menonaktifkan, menghapus kegiatan |
| Kepala Sekolah, Guru BK | Melihat jadwal & memantau pengisian (baca saja) |
| **Wali Kelas** | **Satu-satunya yang mengisi absensi kegiatan**, hanya untuk kelas yang ia wali-i |
| Admin | Boleh mengisi sebagai perwakilan bila wali kelas berhalangan |
| Guru mapel (bukan wali kelas) | Tidak melihat menu ini sama sekali |

Pembatasan tidak hanya di middleware role: `AbsensiKegiatanController::pastikanBoleh()`
mengecek ulang bahwa kelas yang diisi benar-benar kelas wali pengguna, kelas
itu memang sasaran kegiatan, kegiatan aktif, dan tanggalnya memang hari
kegiatan. Jadi URL tidak bisa "diakali" untuk mengabsen kelas orang lain.

---

## 3. Alur pemakaian

**Kesiswaan/Kurikulum** → menu **Kesiswaan › Kegiatan Sekolah** → "Jadwalkan
Kegiatan":

* Nama, jenis (Lomba / Tryout & Asesmen / Classmeeting / Keagamaan / Lainnya)
* Rentang tanggal (boleh satu hari, boleh sebulan penuh)
* **Hari berlangsung** (opsional) — mis. lomba yang hanya digelar tiap Sabtu
  selama Agustus: cukup centang Sabtu, rentang 1–31 Agustus
* **Cakupan**: semua kelas / satu tingkat / kelas tertentu
* **Kirim notifikasi WhatsApp Alfa**: bisa dimatikan per kegiatan

**Wali Kelas** → menu **Kegiatan Mengajar › Absensi Kegiatan Sekolah**
(atau langsung dari kartu "Kegiatan Sekolah Hari Ini" di dashboard) → pilih
kegiatan → tandai Hadir/Sakit/Izin/Alfa → Simpan. Ada tombol "Tandai Semua
Hadir" seperti pada absensi KBM.

**Pemantauan** → menu Kegiatan Sekolah → Detail: matriks **kelas × hari**
yang menunjukkan kelas mana sudah/belum mengisi, siapa yang mengisi, dan
berapa siswa Alfa. Halaman daftar juga menampilkan persentase pengisian tiap
kegiatan.

---

## 4. Notifikasi WhatsApp

Aturan yang berlaku sama untuk absensi KBM maupun kegiatan, dan sekarang
ditulis satu kali saja di `app/Support/NotifikasiAlfa.php` (sebelumnya hanya
ada di dalam `MengajarController`, sehingga rawan bercabang):

1. Yang dikirimi WA hanya siswa dengan status **final** hari itu = Alfa.
2. Satu siswa maksimal satu notifikasi per tanggal (anti-dobel).
3. Pengisian untuk tanggal yang **sudah lewat** tetap dicatat tetapi WA-nya
   sengaja tidak dikirim (statusnya `dilewati`, lengkap dengan alasannya).
4. Kegiatan yang diatur "tanpa notifikasi WhatsApp" juga tercatat `dilewati`.
5. Pengiriman lewat queue, jadi wali kelas tidak menunggu proses WA.

Isi pesannya menyesuaikan konteks: hari biasa menyebut mata pelajaran dan jam
ke berapa, hari kegiatan menyebut **nama kegiatannya** ("Kegiatan : *Lomba
HUT RI ke-81*"). Menu **Notifikasi WhatsApp Ortu** menampilkan konteks ini
beserta penanda "Kegiatan".

> Prasyarat yang tidak berubah: `php artisan queue:work --queue=notifikasi`
> harus tetap berjalan, karena pengiriman WA memakai antrian.

---

## 5. Keputusan teknis penting

**Absensi kegiatan menumpang di tabel `absensi_siswas`, bukan tabel baru.**
Seluruh laporan yang sudah berjalan — Rekap Absensi Kelas, Rekapitulasi
Kepatuhan, dashboard, portal orang tua, notifikasi WA — membaca dari tabel
itu. Dengan menumpang, hari kegiatan otomatis ikut terhitung di semua tempat
tersebut tanpa satu pun laporan perlu ditulis ulang. Konsekuensinya kolom
`jurnal_mengajar_id` dibuat boleh kosong, dan ditambah kolom
`absensi_kegiatan_id` + `sumber` (`kbm` / `kegiatan`).

**Absensi kegiatan menang atas absensi mapel pada hari yang sama.**
`AbsensiSiswa::finalPerHari()` dulu memilih status dari guru mapel dengan jam
paling akhir. Sekarang: kalau ada absensi kegiatan pada tanggal itu, itulah
status final — karena kegiatan menggantikan jam KBM hari itu dan wali kelas
adalah pihak paling berwenang atas kehadiran siswa. Laporan per guru mapel
tidak terpengaruh (ia menyaring `whereHas('jurnal', ...)`).

**Status kegiatan dihitung dari tanggal, tidak disimpan** (akan datang /
berlangsung / selesai), jadi tidak pernah basi karena lupa diperbarui.

**Kegiatan yang absensinya sudah terisi tidak bisa dihapus** — menghapusnya
akan ikut menghapus catatan kehadiran siswa (cascade). Gunakan "nonaktifkan".

---

## 6. Berkas

**Migrasi baru** (jalankan `php artisan migrate`):
* `2026_08_27_000001_create_kegiatan_sekolahs_table.php` (+ tabel `kegiatan_kelas`)
* `2026_08_27_000002_create_absensi_kegiatans_table.php`
* `2026_08_27_000003_add_kegiatan_to_absensi_siswas_table.php`
* `2026_08_27_000004_add_kegiatan_to_notifikasi_alfa_terkirims_table.php`

**Baru:** `app/Models/KegiatanSekolah.php`, `app/Models/AbsensiKegiatan.php`,
`app/Support/NotifikasiAlfa.php`, `app/Http/Controllers/KegiatanSekolahController.php`,
`app/Http/Controllers/AbsensiKegiatanController.php`,
`resources/views/kegiatan/` (index, show, absensi-pilih, absensi-form, partials).

**Diubah:** `AbsensiSiswa` (relasi, prioritas final, konteks), `MengajarController`
(pakai helper notifikasi bersama), `KirimNotifikasiAlfaWhatsapp` (pesan
kegiatan), `NotifikasiAlfaTerkirim`, `DashboardController` + `dashboard/guru`
(kartu kegiatan hari ini), `WaliKelasController`, `RekapController`,
`OrangTuaDashboardController`, `NotifikasiWhatsappController` + view-nya,
`routes/web.php`, `app/Support/Navigasi.php`.

---

## 7. Setelah update

```bash
php artisan migrate
php artisan optimize:clear
# pastikan worker antrian tetap jalan:
php artisan queue:work --queue=notifikasi
```

Uji cepat: jadwalkan satu kegiatan hari ini untuk satu kelas → login sebagai
wali kelas tersebut → dashboard akan menampilkan kartu kegiatan → isi absensi
dengan satu siswa Alfa → cek menu Notifikasi WhatsApp Ortu, barisnya harus
muncul dengan penanda "Kegiatan".
