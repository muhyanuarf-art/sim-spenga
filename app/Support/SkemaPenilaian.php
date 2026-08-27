<?php

namespace App\Support;

use App\Models\KktpTingkat;
use App\Models\PengaturanPenilaian;
use App\Models\TahunAjaran;

/**
 * SATU-SATUNYA TEMPAT RUMUS PENILAIAN DIHITUNG.
 *
 * Semua halaman (form daftar nilai guru mapel, laporan wali kelas,
 * monitoring kurikulum, cetak) memakai kelas ini — supaya angka yang
 * tampil di rapor tidak pernah beda dengan angka yang tampil di daftar
 * nilai gara-gara rumusnya ditulis ulang di dua tempat.
 *
 * =====================================================================
 * RUMUSNYA — sesuai format Daftar Nilai sekolah
 * =====================================================================
 *
 * (1) RT FORMATIF
 *     Rata-rata TPF 1 .. TPF 7. Kolom yang MASIH KOSONG tidak ikut
 *     dihitung (bukan dianggap nol) — kalau guru baru mengisi TPF 1–3
 *     di bulan ketiga, rata-ratanya adalah rata-rata dari 3 nilai itu,
 *     bukan 3 nilai dibagi 7. Dianggap nol hanya akan membuat nilai
 *     sementara siswa terlihat jatuh padahal babnya memang belum diajarkan.
 *
 * (2) RT SUMATIF LINGKUP MATERI  ← INI PERTANYAAN "BUTUH SOLUSI" DI SPEK
 *     Tiap Lingkup Materi punya SEPASANG kolom: SUM (sumatif) dan REM
 *     (remedi, hanya diisi kalau SUM < KKTP). Jadi 1 lingkup materi tidak
 *     boleh dihitung sebagai 2 nilai — kalau SUM dan REM dirata-rata
 *     bersama seluruh kolom, siswa yang remedi otomatis punya lebih
 *     banyak "suara" daripada siswa yang sekali langsung tuntas, dan
 *     nilai LM 1 jadi menekan LM 2–4 hanya karena kebetulan ada remedi.
 *
 *     Yang benar: tiap lingkup materi diringkas dulu menjadi SATU nilai
 *     final, baru nilai-nilai final itu yang dirata-rata.
 *
 *         nilai LM ke-i = ringkas(SUM_i, REM_i)
 *         RT SUMATIF LM = rata-rata dari nilai LM yang sudah terisi
 *
 *     Cara meringkasnya bisa dipilih Kurikulum (kebijakan_remedial):
 *
 *     a. batas_kktp (DEFAULT, dan ini yang disarankan)
 *            nilai = max( SUM , min(REM, KKTP_min) )
 *        Artinya: hasil remedi mengangkat nilai siswa sampai TUNTAS,
 *        tapi tidak lebih tinggi dari KKTP. Ini kebijakan yang paling
 *        umum dipakai sekolah karena adil dua arah — siswa yang remedi
 *        tidak dirugikan (nilainya naik jadi tuntas), tapi juga tidak
 *        melampaui teman yang sudah tuntas sejak sumatif pertama. Bagian
 *        max(SUM, ...) menjaga supaya remedi tidak pernah MENURUNKAN
 *        nilai (mis. siswa nilai 72 lalu remedinya malah 60).
 *
 *     b. tertinggi
 *            nilai = max(SUM, REM)
 *        Hasil terbaik yang dipakai, tanpa batas. Cocok untuk sekolah
 *        yang memandang remedi sebagai kesempatan penuh mengulang.
 *
 *     c. rata_rata
 *            nilai = (SUM + REM) / 2
 *        Kompromi: usaha awal dan hasil remedi sama-sama diperhitungkan.
 *
 * (3) KOMPONEN BERBOBOT 60%
 *     Di format daftar nilai, FORMATIF dan SUMATIF LINGKUP MATERI berbagi
 *     SATU kolom "%BOBOT 60" — porsi masing-masing di dalamnya tidak
 *     disebut. Maka dibuat bisa diatur (default dibagi rata):
 *
 *         N60 = (RT_FORMATIF × komposisi_formatif
 *                + RT_SUMATIF_LM × komposisi_sumatif_lm) / 100
 *
 *     Kalau salah satunya belum ada isinya sama sekali, yang ada dipakai
 *     100% (bukan dianggap nol).
 *
 * (4) NILAI AKHIR (RAPOR)
 *
 *         NA = (N60 × bobot_60 + ASTS × bobot_asts + ASAS × bobot_asas)
 *              ÷ (jumlah bobot komponen yang SUDAH ada nilainya)
 *
 *     Pembaginya sengaja bukan selalu 100. Di tengah semester, ASTS dan
 *     ASAS memang belum ada. Kalau tetap dibagi 100, nilai sementara
 *     siswa akan tampil ±60 padahal ia belum tertinggal apa pun — guru
 *     jadi salah membaca kondisi kelas. Dengan dibagi bobot yang sudah
 *     ada, angka yang tampil adalah "nilai sejauh komponen yang sudah
 *     dinilai", dan barisnya ditandai BELUM LENGKAP (lihat $lengkap)
 *     sampai ketiga komponen terisi.
 *
 * (5) PREDIKAT — diturunkan dari rentang KKTP tingkat itu (mis. 73–82):
 *         D : di bawah KKTP_min          → belum tuntas, perlu remedi
 *         C : KKTP_min .. KKTP_max       → tuntas pada batas minimum
 *         B : di atas KKTP_max, separuh bawah sisa rentang sampai 100
 *         A : separuh atas sisa rentang sampai 100
 *     Untuk KKTP 73–82 hasilnya: D <73, C 73–82, B 83–91, A 92–100.
 */
class SkemaPenilaian
{
    public const KEBIJAKAN = [
        'batas_kktp' => 'Nilai remedi dibatasi maksimal KKTP (disarankan)',
        'tertinggi' => 'Ambil nilai tertinggi antara sumatif & remedi',
        'rata_rata' => 'Rata-rata nilai sumatif & remedi',
    ];

    private function __construct(
        public readonly TahunAjaran $periode,
        public readonly int $tingkat,
        public readonly int $kktpMin,
        public readonly int $kktpMax,
        public readonly int $bobotFormatifSumatif,
        public readonly int $bobotAsts,
        public readonly int $bobotAsas,
        public readonly int $komposisiFormatif,
        public readonly int $komposisiSumatifLm,
        public readonly int $jumlahTpf,
        public readonly int $jumlahLm,
        public readonly string $kebijakanRemedial,
    ) {
    }

    /** Cache per (periode, tingkat) — lihat untuk(). */
    private static array $cache = [];

    /**
     * Skema yang berlaku untuk satu tingkat kelas pada satu periode.
     * Di-cache per (periode, tingkat) karena dipanggil sekali per siswa
     * saat menghitung satu kelas penuh.
     */
    public static function untuk(TahunAjaran $periode, int $tingkat): self
    {
        $kunci = $periode->id.'|'.$tingkat;

        if (isset(self::$cache[$kunci])) {
            return self::$cache[$kunci];
        }

        $pengaturan = PengaturanPenilaian::untukPeriode($periode);
        $kktp = KktpTingkat::untuk($periode, $tingkat);

        return self::$cache[$kunci] = new self(
            periode: $periode,
            tingkat: $tingkat,
            kktpMin: $kktp->kktp_min,
            kktpMax: $kktp->kktp_max,
            bobotFormatifSumatif: $pengaturan->bobot_formatif_sumatif,
            bobotAsts: $pengaturan->bobot_asts,
            bobotAsas: $pengaturan->bobot_asas,
            komposisiFormatif: $pengaturan->komposisi_formatif,
            komposisiSumatifLm: $pengaturan->komposisi_sumatif_lm,
            jumlahTpf: $pengaturan->jumlah_tpf,
            jumlahLm: $pengaturan->jumlah_lm,
            kebijakanRemedial: $pengaturan->kebijakan_remedial,
        );
    }

    /**
     * Buang cache — WAJIB dipanggil setelah Kurikulum menyimpan pengaturan
     * baru, supaya perhitungan ulang di request yang sama sudah memakai
     * bobot/KKTP yang baru, bukan yang sudah terlanjur ter-cache.
     */
    public static function lupakanCache(): void
    {
        self::$cache = [];
        PengaturanPenilaian::lupakanCache();
        KktpTingkat::lupakanCache();
    }

    // =================================================================
    // PERHITUNGAN
    // =================================================================

    /**
     * Hitung seluruh turunan nilai satu siswa untuk satu mata pelajaran.
     *
     * @param  array  $formatif    ['1' => 85, '2' => 78, ...] nilai TPF
     * @param  array  $sumatifLm   ['1' => ['sum' => 70, 'rem' => 75], ...]
     * @return array{
     *     rata_formatif: float|null,
     *     rata_sumatif_lm: float|null,
     *     nilai_lm: array<int, float|null>,
     *     n60: float|null,
     *     nilai_akhir: float|null,
     *     predikat: string|null,
     *     tuntas: bool|null,
     *     lengkap: bool
     * }
     */
    public function hitung(array $formatif, array $sumatifLm, ?float $asts, ?float $asas): array
    {
        $rataFormatif = $this->rataRata($this->angkaValid($formatif));

        $nilaiLm = [];
        for ($i = 1; $i <= $this->jumlahLm; $i++) {
            $nilaiLm[$i] = $this->nilaiLingkupMateri(
                $this->angkaAtauNull($sumatifLm[$i]['sum'] ?? null),
                $this->angkaAtauNull($sumatifLm[$i]['rem'] ?? null),
            );
        }
        $rataSumatifLm = $this->rataRata(array_filter($nilaiLm, fn ($n) => $n !== null));

        // --- Komponen berbobot 60% ---
        $n60 = $this->gabungFormatifSumatif($rataFormatif, $rataSumatifLm);

        // --- Nilai akhir: bagi hanya dengan bobot komponen yang sudah ada ---
        $komponen = [
            [$n60, $this->bobotFormatifSumatif],
            [$this->angkaAtauNull($asts), $this->bobotAsts],
            [$this->angkaAtauNull($asas), $this->bobotAsas],
        ];

        $totalNilai = 0.0;
        $totalBobot = 0;
        foreach ($komponen as [$nilai, $bobot]) {
            if ($nilai !== null && $bobot > 0) {
                $totalNilai += $nilai * $bobot;
                $totalBobot += $bobot;
            }
        }

        $nilaiAkhir = $totalBobot > 0 ? round($totalNilai / $totalBobot, 2) : null;

        // PENTING — predikat & status tuntas ditentukan dari nilai yang
        // BENAR-BENAR TERTULIS di rapor, yaitu nilai akhir yang sudah
        // dibulatkan ke bilangan bulat; bukan dari angka desimalnya.
        // Kalau memakai desimal, lembar nilai bisa menampilkan hal yang
        // saling bertentangan: nilai 82,4 tercetak sebagai "82" (yang
        // masih di dalam rentang KKTP 73–82, artinya predikat C) tetapi
        // predikatnya tertulis B. Begitu juga 72,6 akan tercetak "73"
        // (tuntas) padahal ditandai belum tuntas. Orang tua membaca angka
        // yang tercetak, jadi angka itulah yang harus jadi acuan.
        $nilaiRapor = $nilaiAkhir === null ? null : (float) round($nilaiAkhir);

        // "Lengkap" = setiap komponen yang PUNYA BOBOT sudah ada nilainya.
        // Komponen berbobot 0 (mis. sekolah menonaktifkan ASTS) tidak
        // dianggap kurang.
        $lengkap = collect($komponen)
            ->every(fn ($k) => $k[1] === 0 || $k[0] !== null);

        return [
            'rata_formatif' => $rataFormatif,
            'rata_sumatif_lm' => $rataSumatifLm,
            'nilai_lm' => $nilaiLm,
            'n60' => $n60,
            'nilai_akhir' => $nilaiAkhir,
            'predikat' => $nilaiRapor === null ? null : $this->predikat($nilaiRapor),
            'tuntas' => $nilaiRapor === null ? null : $nilaiRapor >= $this->kktpMin,
            'lengkap' => $lengkap,
        ];
    }

    /**
     * Meringkas SEPASANG kolom (SUM, REM) satu lingkup materi menjadi satu
     * nilai final. Lihat penjelasan lengkap tiap kebijakan di docblock kelas.
     */
    public function nilaiLingkupMateri(?float $sum, ?float $rem): ?float
    {
        if ($sum === null && $rem === null) {
            return null;
        }

        // Remedi diisi tapi sumatifnya kosong — perlakukan remedi sebagai
        // nilai satu-satunya (tetap dibatasi KKTP pada kebijakan default,
        // supaya tidak jadi celah melewati aturan batas).
        if ($sum === null) {
            return $this->kebijakanRemedial === 'batas_kktp'
                ? min($rem, (float) $this->kktpMin)
                : $rem;
        }

        if ($rem === null) {
            return $sum;
        }

        return match ($this->kebijakanRemedial) {
            'tertinggi' => max($sum, $rem),
            'rata_rata' => round(($sum + $rem) / 2, 2),
            // batas_kktp (default)
            default => max($sum, min($rem, (float) $this->kktpMin)),
        };
    }

    /** Gabungan RT Formatif & RT Sumatif LM menjadi satu angka (komponen 60%). */
    public function gabungFormatifSumatif(?float $rataFormatif, ?float $rataSumatifLm): ?float
    {
        if ($rataFormatif === null && $rataSumatifLm === null) {
            return null;
        }

        // Kalau salah satu belum ada isinya, yang ada dipakai penuh —
        // bukan dianggap nol (lihat alasan di docblock kelas).
        if ($rataFormatif === null) {
            return $rataSumatifLm;
        }
        if ($rataSumatifLm === null) {
            return $rataFormatif;
        }

        $totalKomposisi = $this->komposisiFormatif + $this->komposisiSumatifLm;
        if ($totalKomposisi <= 0) {
            return round(($rataFormatif + $rataSumatifLm) / 2, 2);
        }

        return round(
            ($rataFormatif * $this->komposisiFormatif + $rataSumatifLm * $this->komposisiSumatifLm) / $totalKomposisi,
            2
        );
    }

    /** Predikat A/B/C/D dari rentang KKTP tingkat ini. */
    public function predikat(float $nilai): string
    {
        if ($nilai < $this->kktpMin) {
            return 'D';
        }

        if ($nilai <= $this->kktpMax) {
            return 'C';
        }

        // Sisa rentang di atas KKTP_max sampai 100 dibagi dua: separuh
        // bawah B, separuh atas A.
        $batasA = $this->kktpMax + (100 - $this->kktpMax) / 2;

        return $nilai >= $batasA ? 'A' : 'B';
    }

    /** Batas bawah tiap predikat, untuk ditampilkan sebagai keterangan di layar & cetakan. */
    public function rentangPredikat(): array
    {
        $batasA = (int) ceil($this->kktpMax + (100 - $this->kktpMax) / 2);

        return [
            'A' => ['dari' => $batasA, 'sampai' => 100, 'label' => 'Sangat Baik'],
            'B' => ['dari' => $this->kktpMax + 1, 'sampai' => $batasA - 1, 'label' => 'Baik'],
            'C' => ['dari' => $this->kktpMin, 'sampai' => $this->kktpMax, 'label' => 'Cukup'],
            'D' => ['dari' => 0, 'sampai' => $this->kktpMin - 1, 'label' => 'Perlu Bimbingan'],
        ];
    }

    // =================================================================
    // LABEL TAMPILAN
    // =================================================================

    /**
     * Nama kolom sumatif akhir mengikuti semester berjalan:
     * - Semester Ganjil → "ASAS" (Asesmen Sumatif Akhir Semester)
     * - Semester Genap  → "ASAT" (Asesmen Sumatif Akhir Tahun)
     * Sesuai permintaan: ASAS dipakai Desember, ASAT dipakai Juni.
     */
    public function labelSumatifAkhir(): string
    {
        return $this->periode->semester === 'Genap' ? 'ASAT' : 'ASAS';
    }

    public function labelPanjangSumatifAkhir(): string
    {
        return $this->periode->semester === 'Genap'
            ? 'Asesmen Sumatif Akhir Tahun'
            : 'Asesmen Sumatif Akhir Semester';
    }

    /** Teks KKTP untuk header daftar nilai, mis. "73 – 82". */
    public function labelKktp(): string
    {
        return "{$this->kktpMin} – {$this->kktpMax}";
    }

    public function labelKebijakanRemedial(): string
    {
        return self::KEBIJAKAN[$this->kebijakanRemedial] ?? $this->kebijakanRemedial;
    }

    /**
     * Kalimat deskripsi capaian otomatis untuk rapor — dirakit dari data
     * yang SUDAH ada (predikat + bab formatif tertinggi/terendah), jadi
     * guru tetap tidak perlu mengetik rumusan Tujuan Pembelajaran.
     */
    public function deskripsiCapaian(array $formatif, ?float $nilaiAkhir): ?string
    {
        if ($nilaiAkhir === null) {
            return null;
        }

        // Sama seperti di hitung(): predikat mengikuti nilai yang tertulis
        // di rapor (sudah dibulatkan), bukan angka desimalnya.
        $label = $this->rentangPredikat()[$this->predikat((float) round($nilaiAkhir))]['label'];
        $angka = $this->angkaValid($formatif);

        if (count($angka) < 2) {
            return "Capaian kompetensi {$label}.";
        }

        $tertinggi = array_keys($angka, max($angka))[0];
        $terendah = array_keys($angka, min($angka))[0];

        if ($tertinggi === $terendah) {
            return "Capaian kompetensi {$label}.";
        }

        return "Capaian kompetensi {$label}. Menunjukkan penguasaan terbaik pada materi BAB {$tertinggi}"
            .", dan masih perlu penguatan pada materi BAB {$terendah}.";
    }

    // =================================================================
    // internal
    // =================================================================

    /** Hanya nilai yang benar-benar terisi (0 tetap dihitung, kosong/'' tidak). */
    private function angkaValid(array $nilai): array
    {
        $hasil = [];
        foreach ($nilai as $kunci => $satu) {
            $angka = $this->angkaAtauNull($satu);
            if ($angka !== null) {
                $hasil[(int) $kunci] = $angka;
            }
        }

        return $hasil;
    }

    private function angkaAtauNull(mixed $nilai): ?float
    {
        if ($nilai === null || $nilai === '' || ! is_numeric($nilai)) {
            return null;
        }

        return (float) $nilai;
    }

    private function rataRata(array $angka): ?float
    {
        if (empty($angka)) {
            return null;
        }

        return round(array_sum($angka) / count($angka), 2);
    }
}
