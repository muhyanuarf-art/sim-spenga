# Perbaikan: Status Pembinaan Disederhanakan, Tahap Otomatis, Kasus Ikut Selesai

## 1. Status Pembinaan — sekarang cuma 2 pilihan

Dulu: Direncanakan, Berlangsung, Selesai, Tidak Berhasil (4 pilihan).
Sekarang: **"Pembinaan"** dan **"Selesai"** saja.

Data lama otomatis dipetakan saat migration jalan:
`Direncanakan/Berlangsung/Tidak Berhasil` → `Pembinaan`, `Selesai` tetap `Selesai`.

## 2. Tahap — sekarang 100% otomatis dari sistem

Field Tahap di form Catat Pembinaan **bukan dropdown pilihan lagi** —
tampil sebagai kotak read-only "Tahap X (otomatis)", dihitung dari poin
aktif siswa saat itu (`PoinSiswaService::rekomendasiTahap()`). Sama
seperti Kategori/Poin di form Kasus, **server tidak menerima nilai
Tahap dari form** — selalu dihitung ulang di server.

⚠️ **Catatan penting**: karena rumus otomatis ini hanya menghitung
Tahap 1-5 (dari rentang poin aktif 5 s.d >75), **Tahap 6 dan 7 tidak
akan pernah muncul otomatis** — keduanya di desain awal memang butuh
pertimbangan manusia (mis. "tahap 5 sudah dilakukan tapi belum
berubah"), bukan sesuatu yang bisa dihitung murni dari angka poin.
Kalau nanti sekolah tetap butuh jalur untuk mencatat Tahap 6/7 secara
manual untuk kasus-kasus ekstrem, kabari saya — bisa saya tambahkan
sebagai opsi terpisah tanpa mengubah perilaku otomatis yang sudah ada.

## 3. Kasus otomatis "Selesai" mengikuti Pembinaan

Sekarang kalau Pembinaan seorang siswa dicatat/diubah statusnya jadi
**"Selesai"**, dan pembinaan itu terkait ke 1 Kasus tertentu, maka
**Kasus itu ikut otomatis ditandai "Selesai"** — sehingga otomatis
hilang dari daftar **"📋 Kasus Belum Selesai"** di Dashboard BK, dan
statusnya di halaman Kasus/Pelanggaran juga ikut berubah jadi "Selesai".

Berlaku di 2 tempat:
- Saat **mencatat pembinaan baru** langsung dengan status "Selesai".
- Saat **mengubah pembinaan yang sudah ada** jadi "Selesai".

## File yang diubah

| File | Keterangan |
|---|---|
| `database/migrations/..._simplify_pembinaan_status.php` | Ubah enum status + migrasi data lama |
| `app/Http/Controllers/BkPembinaanController.php` | Tahap otomatis, status 2 pilihan, kasus ikut selesai |
| `app/Http/Controllers/BkDashboardController.php` | Sesuaikan query "Sedang Dalam Pembinaan" dengan status baru |
| `resources/views/bk/siswa/show.blade.php` | Modal Catat Pembinaan: Tahap jadi read-only, status 2 opsi |
| `resources/views/bk/pembinaan/index.blade.php` | Filter & badge status disederhanakan |

## Cara pasang

```bash
php artisan migrate
php artisan view:clear
```

## Testing

1. Buka profil siswa yang punya poin aktif, mis. 25 (Tahap rekomendasi
   harusnya "Tahap 2") → klik **Catat Pembinaan** → field Tahap harus
   tampil "Tahap 2" (abu-abu, tidak bisa diklik/diubah).
2. Dropdown Status harus cuma ada 2 pilihan: Pembinaan, Selesai.
3. Catat pembinaan terkait 1 kasus tertentu dengan status **Selesai**
   langsung → cek di Dashboard BK, kasus itu harus **hilang** dari
   "Kasus Belum Selesai".
4. Catat pembinaan lain dengan status "Pembinaan" (belum selesai) →
   kasus terkait harus tetap muncul di "Kasus Belum Selesai" dengan
   status "Dalam Pembinaan".
5. Edit pembinaan yang tadinya "Pembinaan" jadi "Selesai" → kasus
   terkait harus ikut berubah jadi "Selesai" juga.
