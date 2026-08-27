<?php

namespace App\Support;

/**
 * PENYEBAR SKOR BUTIR SOAL — inti dari Analisis Hasil Tes Sumatif.
 *
 * =====================================================================
 * MASALAH YANG DIPECAHKAN
 * =====================================================================
 * Guru sudah memasukkan NILAI tes sumatif lingkup materi di Daftar Nilai
 * (mis. siswa A = 90). Lembar Analisis Hasil Tes menuntut rincian skor
 * per nomor soal 1–20. Rinciannya harus:
 *
 *   a. berjumlah PERSIS sama dengan nilai yang sudah diinput (90 tetap
 *      90), supaya lembar analisis tidak pernah bertentangan dengan rapor;
 *   b. tampak wajar — bukan pola yang sama untuk semua siswa;
 *   c. konsisten antar siswa: soal yang sukar harus banyak dijawab salah
 *      oleh SATU KELAS, bukan acak sendiri-sendiri. Tanpa ini, kolom
 *      "daya serap per butir soal" akan rata semua dan analisisnya tidak
 *      berguna untuk menentukan soal mana yang perlu dibahas ulang;
 *   d. TETAP SAMA setiap kali halaman dibuka atau dicetak ulang. Dokumen
 *      resmi tidak boleh berubah angkanya hanya karena di-muat ulang.
 *
 * =====================================================================
 * CARA KERJANYA
 * =====================================================================
 * Tiap butir soal bernilai maksimal 1 poin (boleh skor sebagian, mis.
 * 0,6 untuk jawaban yang benar separuh). Dengan N butir soal:
 *
 *   poin dibutuhkan  T = nilai x N / 100
 *   poin hilang      H = N - T
 *
 * Contoh N = 20, nilai = 83:  T = 16,6  sehingga  H = 3,4.
 * Artinya siswa itu harus kehilangan 3,4 poin: 3 butir dijawab salah
 * (skor 0) dan 1 butir dijawab separuh (skor 1 - 0,4 = 0,6).
 * Jumlah skor = 16,6, lalu 16,6 / 20 x 100 = 83. Sama persis.
 *
 * BUTIR MANA yang salah ditentukan begini:
 *
 *   1. Tiap butir soal diberi TINGKAT KESUKARAN tetap, diacak dari benih
 *      (kelas + mapel + periode + lingkup materi). Karena benihnya sama
 *      untuk seluruh kelas, butir yang sukar sukar bagi semua orang.
 *   2. Tiap siswa diberi GORESAN ACAK kecil per butir, dari benih yang
 *      juga memuat id siswa — supaya dua siswa dengan nilai sama tidak
 *      salah di nomor yang persis sama.
 *   3. Butir diurutkan menurun berdasarkan (kesukaran + goresan), lalu
 *      poin yang hilang dihabiskan dari butir tersukar lebih dulu.
 *
 * Bobot goresan (BOBOT_GORESAN) sengaja lebih kecil daripada rentang
 * kesukaran: cukup untuk membuat variasi antar siswa, tapi tidak sampai
 * menghapus pola "soal ini memang sukar" yang justru dicari dari analisis.
 *
 * Seluruh keacakan memakai crc32() atas teks benih, BUKAN rand()/shuffle().
 * Jadi hasilnya sama di komputer mana pun, kapan pun, tanpa perlu
 * menyimpan satu pun angka skor butir ke database.
 */
class AnalisisButirSoal
{
    /** Skor maksimal tiap butir soal. */
    public const SKOR_MAKS_BUTIR = 1.0;

    /**
     * Seberapa besar variasi antar siswa dibanding tingkat kesukaran soal.
     * Kesukaran tersebar di rentang 0–1; goresan acak 0–0,7 cukup untuk
     * membuat siswa tidak salah di nomor yang sama persis, tanpa membuat
     * urutan kesukaran soal hilang sama sekali.
     */
    private const BOBOT_GORESAN = 0.7;

    /** Tingkat kesukaran tiap butir, 0 (mudah) .. 1 (sukar). Sama untuk 1 kelas. */
    private array $kesukaran;

    /**
     * @param  int     $jumlahSoal  banyaknya butir soal (mis. 20)
     * @param  string  $benihKelas  penciri lembar: kelas + mapel + periode + lingkup materi
     */
    public function __construct(
        public readonly int $jumlahSoal,
        private readonly string $benihKelas,
    ) {
        $this->kesukaran = $this->susunKesukaran();
    }

    /**
     * Rincian skor butir soal satu siswa.
     *
     * @param  float|null  $nilai       nilai sumatif 0–100 yang sudah diinput guru
     * @param  string      $benihSiswa  penciri siswa (agar tiap siswa berbeda)
     * @return array<int, float>  [1 => 1.0, 2 => 0.0, 3 => 0.6, ...]; kosong bila nilai belum ada
     */
    public function skorSiswa(?float $nilai, string $benihSiswa): array
    {
        if ($nilai === null || $this->jumlahSoal < 1) {
            return [];
        }

        $nilai = max(0.0, min(100.0, $nilai));

        // Poin yang harus hilang supaya jumlah skornya pas dengan nilai.
        $poinDibutuhkan = round($nilai * $this->jumlahSoal / 100, 4);
        $poinHilang = round($this->jumlahSoal * self::SKOR_MAKS_BUTIR - $poinDibutuhkan, 4);

        $skor = array_fill_keys(range(1, $this->jumlahSoal), self::SKOR_MAKS_BUTIR);

        // Nilai 100 — semua butir benar.
        if ($poinHilang <= 0) {
            return $skor;
        }

        // Urutan "paling mungkin dijawab salah" untuk siswa ini.
        $prioritas = [];
        for ($i = 1; $i <= $this->jumlahSoal; $i++) {
            $prioritas[$i] = $this->kesukaran[$i]
                + $this->acak($benihSiswa.'|butir|'.$i) * self::BOBOT_GORESAN;
        }
        arsort($prioritas);

        // Habiskan poin yang hilang dari butir tersukar lebih dulu.
        $sisa = $poinHilang;
        foreach (array_keys($prioritas) as $nomor) {
            if ($sisa <= 0) {
                break;
            }

            $dikurangi = min(self::SKOR_MAKS_BUTIR, $sisa);
            // Dibulatkan 4 desimal, BUKAN 2. Kalau dibulatkan 2 di sini,
            // jumlah skornya bisa meleset dari nilai aslinya ketika banyak
            // soal tidak membagi habis skala 100 — mis. 25 soal dengan
            // nilai 87,5 menghasilkan sisa 0,125; dibulatkan jadi 0,13
            // dan totalnya berubah menjadi 87,52. Presisi penuh dipakai
            // untuk perhitungan, pembulatan hanya dilakukan saat ditampilkan.
            $skor[$nomor] = round(self::SKOR_MAKS_BUTIR - $dikurangi, 4);
            $sisa = round($sisa - $dikurangi, 4);
        }

        ksort($skor);

        return $skor;
    }

    /**
     * Jumlah skor siswa dalam skala 0–100 — angka inilah yang masuk kolom
     * "Jml. Skor" dan wajib sama dengan nilai di Daftar Nilai.
     */
    public function jumlahSkor(array $skorButir): ?float
    {
        if (empty($skorButir) || $this->jumlahSoal < 1) {
            return null;
        }

        return round(array_sum($skorButir) / ($this->jumlahSoal * self::SKOR_MAKS_BUTIR) * 100, 2);
    }

    /**
     * Tingkat kesukaran butir soal menurut HASIL TES (berbeda dari
     * kesukaran internal yang dipakai menyebar skor di atas): proporsi
     * skor yang berhasil diraih seluruh peserta pada butir itu.
     * Istilah baku analisis butir soal:
     * P >= 0,70 mudah; 0,30 <= P < 0,70 sedang; P < 0,30 sukar.
     */
    public static function labelKesukaran(?float $dayaSerapPersen): string
    {
        if ($dayaSerapPersen === null) {
            return '-';
        }

        return match (true) {
            $dayaSerapPersen >= 70 => 'Mudah',
            $dayaSerapPersen >= 30 => 'Sedang',
            default => 'Sukar',
        };
    }

    public static function warnaKesukaran(?float $dayaSerapPersen): string
    {
        return match (self::labelKesukaran($dayaSerapPersen)) {
            'Mudah' => 'bg-emerald-50 text-emerald-700',
            'Sedang' => 'bg-amber-50 text-amber-700',
            'Sukar' => 'bg-rose-50 text-rose-700',
            default => 'bg-slate-100 text-slate-500',
        };
    }

    // =================================================================
    // internal
    // =================================================================

    /**
     * Tingkat kesukaran tiap butir: nilai 0..1 yang tersebar RATA (bukan
     * menumpuk di tengah), urutannya diacak dari benih kelas. Tersebar
     * rata supaya lembar analisis selalu memuat campuran soal mudah,
     * sedang, dan sukar — seperti hasil tes yang sebenarnya.
     */
    private function susunKesukaran(): array
    {
        // Urutkan nomor soal berdasarkan angka acak dari benih kelas,
        // lalu bagikan peringkat kesukaran 0..1 menurut urutan itu.
        $kunciAcak = [];
        for ($i = 1; $i <= $this->jumlahSoal; $i++) {
            $kunciAcak[$i] = $this->acak($this->benihKelas.'|kesukaran|'.$i);
        }
        asort($kunciAcak);

        $hasil = [];
        $pembagi = max(1, $this->jumlahSoal - 1);
        $peringkat = 0;
        foreach (array_keys($kunciAcak) as $i) {
            $hasil[$i] = $peringkat / $pembagi;
            $peringkat++;
        }

        ksort($hasil);

        return $hasil;
    }

    /**
     * Angka acak TETAP di rentang [0, 1) dari sebuah teks benih.
     * Memakai crc32 supaya hasilnya sama di semua komputer & versi PHP —
     * berbeda dengan rand()/shuffle() yang bergantung pada keadaan proses.
     */
    private function acak(string $benih): float
    {
        return (crc32($benih) % 1000003) / 1000003;
    }
}
