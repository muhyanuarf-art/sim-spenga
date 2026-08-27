# Modul Penilaian — Daftar Nilai & Nilai Rapor

Dibangun mengikuti ketentuan pada dokumen *Perintah untuk Penilaian*, ditambah
beberapa hal yang memang dibutuhkan agar layak dipakai sebagai SIM sekolah
(finalisasi, monitoring, peringkat kelas, deskripsi capaian).

---

## 1. Alur singkat

```
Kurikulum                 Guru Mata Pelajaran            Wali Kelas
─────────                 ───────────────────            ──────────
Atur bobot & KKTP   ──►   Isi Daftar Nilai       ──►     Nilai Rapor Kelas
(60% / 20% / 20%)         (TPF, LM+REM, ASTS,            (otomatis, tanpa
KKTP 73–82 per tingkat     ASAS/ASAT)                     input ulang)
                          └─► Finalisasi (terkunci)
                                    │
Monitoring Input Nilai ◄────────────┘
```

Nilai akhir **tidak pernah diketik ulang** oleh wali kelas — begitu guru mapel
menyimpan, angkanya langsung muncul di laporan wali kelas.

---

## 2. Menu baru

| Menu | Route | Role |
|---|---|---|
| Penilaian › **Daftar Nilai** | `nilai.pilih`, `nilai.form` | Guru, Kurikulum, Kepsek, Admin |
| Penilaian › **Nilai Rapor Kelas** | `nilai.rekap-kelas` | Wali Kelas, Guru BK, Kurikulum, Kepsek, Admin |
| Penilaian › **Nilai per Mata Pelajaran** | `nilai.per-mapel` | idem |
| Penilaian › **Monitoring Input Nilai** | `nilai.monitoring` | Kurikulum, Kepsek, Admin |
| Pengaturan › **Pengaturan Penilaian** | `penilaian.pengaturan.edit` | Kurikulum, Admin |

Pembagian hak akses:

- **Mengisi nilai**: guru hanya untuk kelas & mapel yang di-mapping-kan kepadanya
  (`guru_mengajar_kelas`); Admin boleh mewakili guru yang berhalangan.
- **Membaca & mencetak lembar mana pun**: + Kurikulum & Kepala Sekolah.
- **Membuka kunci lembar yang sudah final**: hanya Kurikulum & Admin.
- Kesiswaan & TU tidak diberi akses ke modul ini.

---

## 3. Bentuk Daftar Nilai

Persis lembar Excel yang selama ini dipakai, dengan penyesuaian istilah yang diminta:

| Kolom | Keterangan |
|---|---|
| **TPF 1 … TPF 7** | Penilaian formatif BAB ke-n, isi 0–100. Tidak ada input Tujuan Pembelajaran. |
| **RT** (formatif) | Rata-rata TPF yang **sudah terisi** — kolom kosong tidak dianggap nol. |
| **LM 1 … LM 4** | Tiap Lingkup Materi punya sepasang kolom **SUM** (dulu "S") dan **REM** (dulu "R"). |
| **RT** (sumatif LM) | Rata-rata nilai lingkup materi, remedi sudah diperhitungkan. |
| **%BOBOT 60** | Gabungan Formatif + Sumatif Lingkup Materi. Bobotnya bisa diatur Kurikulum. |
| **ASTS** (dulu "STS") | Asesmen Sumatif Tengah Semester, bobot 20% (bisa diatur). |
| **ASAS / ASAT** (dulu "SAS") | Otomatis **ASAS** pada Semester Ganjil, **ASAT** pada Semester Genap. Bobot 20% (bisa diatur). |
| **NILAI AKHIR (RAPOR)** | Hasil pembobotan. |
| **PREDIKAT** | A/B/C/D, diturunkan dari rentang KKTP. |

Kolom RT, %BOBOT, Nilai Akhir, dan Predikat **terhitung langsung saat guru
mengetik**, sebelum disimpan. Kolom REM otomatis disorot merah bila nilai SUM-nya
di bawah KKTP. Tersedia tombol **Cetak / Export PDF** lengkap dengan KOP surat dan
blok tanda tangan.

---

## 4. Rumus — termasuk jawaban atas "RT2 sumatif butuh solusi"

Seluruh rumus ada di satu tempat: [`app/Support/SkemaPenilaian.php`](app/Support/SkemaPenilaian.php).

### Masalahnya

Tiap Lingkup Materi punya **sepasang** kolom (SUM dan REM). Kalau semua kolom itu
dirata-rata bersama sebagai nilai yang setara, siswa yang remedi otomatis punya
lebih banyak "suara" daripada siswa yang sekali langsung tuntas — dan LM 1 jadi
menekan LM 2–4 hanya karena kebetulan ada remedi di sana.

### Solusinya

Tiap lingkup materi **diringkas dulu menjadi satu nilai**, baru dirata-rata:

```
nilai LM ke-i  = ringkas(SUM_i, REM_i)
RT SUMATIF LM  = rata-rata nilai LM yang sudah terisi
```

Cara meringkasnya dapat dipilih Kurikulum:

| Kebijakan | Rumus | Kapan cocok |
|---|---|---|
| **Dibatasi KKTP** *(bawaan)* | `max(SUM, min(REM, KKTP_min))` | Adil dua arah: yang remedi tetap tuntas, tapi tidak melampaui yang sudah tuntas sejak awal. Bagian `max(...)` menjaga agar remedi tidak pernah menurunkan nilai. |
| Tertinggi | `max(SUM, REM)` | Remedi dipandang kesempatan penuh mengulang. |
| Rata-rata | `(SUM + REM) / 2` | Usaha awal & hasil remedi sama-sama dihitung. |

### Nilai akhir

```
N60 = (RT_Formatif × komposisi_F + RT_SumatifLM × komposisi_S) / 100

NA  = (N60 × 60% + ASTS × 20% + ASAS × 20%)
      ÷ jumlah bobot komponen yang SUDAH ada nilainya
```

Pembagi sengaja **bukan selalu 100**. Di tengah semester ASTS dan ASAS memang belum
ada; kalau tetap dibagi 100, nilai sementara siswa akan tampil ±60 padahal ia belum
tertinggal apa pun. Baris seperti itu ditandai **belum lengkap** (tanda `*` di
laporan wali kelas) dan lembarnya belum bisa difinalisasi.

### Predikat

Diturunkan dari rentang KKTP tingkat tersebut. Untuk KKTP 73–82:

| Predikat | Rentang | Arti |
|---|---|---|
| A | 91 – 100 | Sangat Baik |
| B | 83 – 90 | Baik |
| C | 73 – 82 | Cukup (tuntas pada batas minimum) |
| D | 0 – 72 | Perlu Bimbingan (belum tuntas) |

Predikat dan status tuntas dihitung dari nilai yang **benar-benar tertulis di
rapor** (sudah dibulatkan), bukan angka desimalnya — supaya lembar nilai tidak
pernah menampilkan hal yang saling bertentangan (mis. tercetak "82" tetapi
predikatnya B).

---

## 5. Yang bisa diatur Kurikulum

Menu **Pengaturan › Pengaturan Penilaian**, berlaku **per periode** (tahun ajaran +
semester) — mengubah bobot semester ini tidak mengubah angka rapor semester lalu.

- Bobot Formatif+Sumatif LM / ASTS / ASAS — divalidasi harus berjumlah tepat 100%.
- Komposisi di dalam bobot 60% (Formatif : Sumatif LM), bawaan 50:50.
- KKTP minimum & maksimum tiap tingkat (7, 8, 9) — bawaan 73–82.
- Jumlah kolom TPF (bawaan 7) dan Lingkup Materi (bawaan 4).
- Kebijakan perhitungan remedi.

Menyimpan perubahan akan **menghitung ulang seluruh nilai pada periode itu**,
supaya angka di layar guru dan di laporan wali kelas tidak pernah berbeda.

---

## 6. Tambahan di luar dokumen (agar layak sebagai SIM sekolah)

- **Finalisasi lembar.** Setelah guru menekan Finalisasi, nilai terkunci sehingga
  tidak berubah diam-diam setelah dipakai menyusun rapor. Ditolak bila masih ada
  siswa yang komponennya belum lengkap. Hanya Kurikulum/Admin yang bisa membuka
  kunci, dan tercatat siapa & kapan.
- **Monitoring Input Nilai** untuk Kurikulum & Kepala Sekolah: mapel mana di kelas
  mana yang belum masuk, siapa gurunya, berapa persen kemajuannya.
- **Peringkat kelas** pada laporan wali kelas. Nilai sama mendapat peringkat sama;
  siswa yang belum punya nilai tidak diberi peringkat.
- **Usulan deskripsi capaian** otomatis — dirakit dari predikat dan BAB formatif
  tertinggi/terendah, jadi guru tetap tidak perlu mengetik rumusan Tujuan
  Pembelajaran. Siap disalin ke kolom deskripsi rapor.
- **Statistik lembar**: rata-rata kelas, tertinggi, terendah, jumlah belum tuntas.
- Siswa yang **pindah kelas** tetap tampil pada lembar tempat nilainya sudah
  terlanjur diisi (pola yang sama dengan rekap absensi).
- Semua aksi tulis menghormati **kunci periode** (`periode-aktif` / HTTP 423).

---

## 7. Berkas

| Berkas | Isi |
|---|---|
| `database/migrations/2026_08_27_000005_create_penilaian_tables.php` | 4 tabel: `pengaturan_penilaians`, `kktp_tingkats`, `penilaian_kelas_mapels`, `nilai_siswas` |
| `app/Support/SkemaPenilaian.php` | **Satu-satunya tempat rumus dihitung** |
| `app/Models/{PengaturanPenilaian,KktpTingkat,PenilaianKelasMapel,NilaiSiswa}.php` | Model |
| `app/Http/Controllers/Nilai{,WaliKelas,Monitoring}Controller.php` | Pengisian, laporan, monitoring |
| `app/Http/Controllers/PengaturanPenilaianController.php` | Bobot & KKTP |
| `resources/views/nilai/*.blade.php` | Lembar & laporan |
| `resources/views/penilaian/pengaturan.blade.php` | Pengaturan Kurikulum |
| `resources/js/app.js` (`barisNilai`) | Cermin rumus untuk pratinjau di layar — **server tetap yang berwenang** |

> Kalau rumus di `SkemaPenilaian.php` diubah, ubah juga `barisNilai()` di
> `resources/js/app.js` (dan sebaliknya) agar angka pratinjau tidak berbeda dengan
> angka yang akhirnya tersimpan.
