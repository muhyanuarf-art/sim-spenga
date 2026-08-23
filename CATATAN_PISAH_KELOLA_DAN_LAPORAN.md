# Revisi: Pisah "Kelola Kegiatan" (Edit/Hapus) dari "Anggota & Laporan"

## Apa yang berubah
Sesuai masukan Anda — Edit & Hapus itu untuk mengelola DATA kegiatan itu
sendiri (misal ganti pembina), sedangkan Anggota/Absensi/Rekap itu jalan
pintas ke halaman terkait (laporan). Dua hal berbeda, sekarang dipisah
jelas secara visual:

1. **Menu titik-tiga (⋮)** di pojok kanan atas tiap kartu — isinya "Edit
   Kegiatan" dan "Hapus Kegiatan". Dikemas jadi dropdown supaya jelas ini
   aksi berbeda dari 3 tombol di bawahnya, dan tidak memakan tempat di
   layar sempit.
2. **3 ubin besar** ("Anggota", "Absensi", "Rekap") di bawah nama kegiatan,
   diberi label bagian "Anggota & Laporan" — ikon lebih besar, area sentuh
   lebih luas (dulu teks kecil berjejer, sekarang kotak besar dengan ikon
   di atas + label di bawah), jauh lebih mudah disentuh lewat HP.

Tidak ada perubahan fungsi/route — murni tata letak & ukuran, supaya lebih
rapi dan nyaman dipakai di layar sempit maupun lebar.

## File yang diubah
- `resources/views/ekstrakurikuler/index.blade.php`

**Tidak perlu migrasi baru.**

## Cara menerapkan
1. Timpa file di atas ke `C:\laragon\www\sim-spenga\resources\views\ekstrakurikuler\index.blade.php`.
2. Tidak perlu migrasi, tidak perlu `npm run build`.
3. Test dari HP (atau perkecil browser): pastikan menu titik-tiga di pojok
   kanan atas kartu bisa dibuka, "Edit Kegiatan" & "Hapus Kegiatan" jelas
   terpisah, dan 3 ubin Anggota/Absensi/Rekap nyaman disentuh.
