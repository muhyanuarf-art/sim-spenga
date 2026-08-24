# Perbaikan: Blok tanda tangan terpotong di antara 2 halaman saat Cetak

## Penyebab
Ini masalah CSS "page break" yang umum terjadi kalau bagian bawah suatu
halaman cetak pas-pasan — browser membagi konten APA ADANYA berdasarkan
tinggi halaman, tanpa tahu bahwa "Bumiayu, 24 Agustus 2026 / Mengetahui /
Kepala Sekolah," itu harus tetap satu kesatuan dengan nama & NIP di
bawahnya. Kalau kebetulan pas di batas halaman, ya terpotong tanggung.

## Perbaikan
Ditambahkan aturan CSS baru `.cetak-utuh` yang memberi tahu browser:
"blok ini jangan pernah dipotong di tengah — kalau tidak muat di sisa
halaman sekarang, pindahkan SELURUH bloknya ke halaman berikutnya."
Sudah dipasang di kedua komponen blok tanda tangan yang dipakai di semua
laporan (`blok-tanda-tangan` untuk 1 penanda tangan, `blok-tanda-tangan-dua`
untuk 2 penanda tangan seperti di screenshot Anda — Kepala Sekolah &
Wali Kelas).

Otomatis berlaku di SEMUA laporan yang pakai kedua komponen ini (Rekap
Absensi Kelas, Jurnal Kelas, Rekapitulasi, Pemanggilan Ortu, Pembinaan,
Laporan Guru, dll) — tidak perlu ubah file laporan satu-satu.

## Catatan tambahan (di luar kendali kode)
Baris kecil di screenshot Anda seperti "127.0.0.1:8000/wali-kelas/absensi-
bulanan" dan "8/24/26, 9:39 PM ... Rekap Absensi Bulanan - SIM-SPENGA" itu
**bukan bagian dari halaman**, itu header/footer yang otomatis ditambahkan
BROWSER saat print preview (alamat URL & judul tab). Ini tidak bisa
diatur dari kode aplikasi — cara menghilangkannya: di jendela print/
preview, cari opsi **"Headers and footers"** (biasanya di bagian "More
settings") dan matikan.

## File yang diubah
- `resources/css/app.css` — aturan baru `.cetak-utuh`.
- `resources/views/components/blok-tanda-tangan.blade.php` — tambah class `cetak-utuh`.
- `resources/views/components/blok-tanda-tangan-dua.blade.php` — tambah class `cetak-utuh`.

**Tidak ada migrasi baru.**

## Cara menerapkan (di Laragon)
1. Timpa ketiga file di atas ke `C:\laragon\www\sim-spenga`.
2. **Wajib** `npm run build` (karena `app.css` diubah).
3. Coba Cetak salah satu laporan yang jadi contoh Anda (Rekap Absensi
   Bulanan Wali Kelas) — pastikan blok "Bumiayu, tanggal ... / Mengetahui
   / Kepala Sekolah / nama / NIP / Wali Kelas / nama / NIP" sekarang utuh
   di 1 halaman, tidak terpotong lagi. Kalau tabelnya panjang, blok tanda
   tangan sekarang akan otomatis pindah semua ke halaman berikutnya kalau
   memang tidak muat, bukan terpotong di tengah.
