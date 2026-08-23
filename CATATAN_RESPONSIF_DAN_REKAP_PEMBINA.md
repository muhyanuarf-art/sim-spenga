# Revisi: Menu Ekstrakurikuler jadi kartu (mobile-friendly) + Rekap sertakan Pembina

## 1. Daftar kegiatan jadi kartu, bukan tabel
Sebelumnya tombol Anggota/Absensi/Rekap/Edit/Hapus dijejalkan dalam 1 sel
kolom "Aksi" di tabel — di HP jadi sempit & harus geser ke samping.
Sekarang tiap kegiatan tampil sebagai **kartu** yang melebar penuh:
- Baris atas: nama kegiatan + badge status.
- Baris kedua: nama pembina & keterangan.
- Baris tombol aksi yang otomatis TURUN KE BAWAH (wrap) kalau layar
  sempit, bukan terpotong/harus di-scroll ke samping.
- Form Edit tampil di dalam kartu yang sama (bukan baris tabel terpisah),
  jadi rapi di layar sempit maupun lebar.

Tidak ada perubahan fungsi — semua tombol & aksinya persis sama seperti
sebelumnya, cuma tata letaknya yang dibuat responsif.

## 2. Rekap sekarang sertakan Pembina
Sebelumnya Rekap Absensi Ekskul cuma menampilkan tabel siswa. Sekarang ada
2 tabel:
1. **Absensi Siswa** (seperti sebelumnya).
2. **Absensi Pembina** (baru) — format sama (grid tanggal 1-31, S/I/A),
   kolom NIS diganti kolom "Jenis" (badge "Sekolah" / "Luar Sekolah") karena
   pembina tidak punya NIS. Pembina yang sudah dikeluarkan dari daftar
   pembina tapi masih punya data absensi bulan itu tetap muncul (sama
   prinsipnya dengan siswa) — datanya tidak hilang.

## File yang diubah
- `resources/views/ekstrakurikuler/index.blade.php` — tabel diganti kartu.
- `app/Http/Controllers/EkskulRekapController.php` — tambah method
  `rekapPembina()`, method `bulanan()` mengirim 2 variabel (`rekap` untuk
  siswa, `rekapPembina` untuk pembina) ke view.
- `resources/views/ekstrakurikuler/rekap-bulanan.blade.php` — tambah tabel
  kedua untuk pembina.

**Tidak perlu migrasi baru.**

## Cara menerapkan (di Laragon)
1. Timpa ketiga file di atas ke `C:\laragon\www\sim-spenga`.
2. Tidak perlu migrasi, tidak perlu `npm run build`.
3. Test: buka menu Ekstrakurikuler dari HP (atau perkecil browser) →
   pastikan tombol Anggota/Absensi/Rekap/Edit/Hapus tersusun rapi tanpa
   perlu geser ke samping. Buka Rekap kegiatan yang sudah ada data absensi
   pembina → pastikan tabel "Absensi Pembina" muncul dengan data yang
   sesuai.
