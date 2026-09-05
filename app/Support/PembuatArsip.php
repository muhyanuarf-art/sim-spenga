<?php

namespace App\Support;

use App\Models\ArsipSemester;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use Throwable;
use ZipArchive;

/**
 * MEMBUAT ARSIP SEMESTER — seluruh laporan menjadi satu ZIP berisi PDF.
 *
 * =====================================================================
 * CARA KERJANYA
 * =====================================================================
 * Untuk tiap laporan di DaftarLaporanArsip, pembuat ini menjalankan
 * PERMINTAAN INTERNAL ke rute aslinya — persis seperti seorang Admin
 * membuka halaman itu di peramban — lalu mengubah HTML-nya menjadi PDF.
 *
 * Keuntungannya: tidak ada satu pun kueri atau rumus yang digandakan.
 * Kalau cara menghitung nilai akhir berubah besok, arsipnya ikut berubah
 * dengan sendirinya, karena yang dirender memang halaman yang sama.
 *
 * =====================================================================
 * SATU PDF PER LAPORAN, BUKAN PER KELAS
 * =====================================================================
 * Laporan yang berlaku per kelas dirender berulang untuk tiap kelas,
 * lalu SELURUHNYA digabung ke dalam satu PDF dengan tiap kelas sebagai
 * bab. Sekolah 18 kelas menghasilkan satu berkas 18 bab, bukan 18
 * berkas — dan berkas berjilid seperti itulah yang bisa diserahkan ke
 * asesor, bukan setumpuk potongan.
 *
 * =====================================================================
 * KENAPA HTML-nya DIBERSIHKAN LEBIH DULU
 * =====================================================================
 * Halaman aplikasi berisi sidebar, tombol, dan penyaring yang tidak ada
 * artinya di atas kertas. Semuanya sudah ditandai `no-print` oleh
 * halaman itu sendiri — penanda yang sama yang dipakai tombol Cetak di
 * peramban — jadi pembersihannya memakai penanda yang sudah ada, bukan
 * daftar baru yang harus dirawat terpisah.
 */
class PembuatArsip
{
    private const FOLDER = 'arsip';

    /**
     * true bila peramban tersedia dan dipakai. Menentukan bentuk HTML yang
     * disiapkan: Chrome menerima halaman utuh beserta CSS-nya, mPDF hanya
     * menerima isi yang sudah ditelanjangi.
     */
    private readonly bool $pakaiChrome;

    /** Diisi saat jalankan(); dipakai laporkan() untuk menulis kemajuan. */
    private int $arsipId = 0;

    public function __construct(
        private readonly TahunAjaran $periode,
        private readonly User $sebagai,
    ) {
        $this->pakaiChrome = PencetakChrome::tersedia();
    }

    /**
     * Kerjakan seluruhnya. Mengembalikan ArsipSemester yang sudah terisi.
     */
    public function jalankan(ArsipSemester $arsip): ArsipSemester
    {
        $this->arsipId = $arsip->id;

        $kerja = storage_path('app/private/arsip-sementara-'.uniqid());
        @mkdir($kerja, 0755, true);

        try {
            $berkas = $this->buatSemuaPdf($kerja);

            if ($berkas === 0) {
                throw new \RuntimeException('Tidak ada satu pun laporan yang berhasil dirender.');
            }

            $path = $this->bungkusJadiZip($kerja);

            // WAJIB disegarkan lebih dulu. laporkan() menulis progres &
            // langkah LANGSUNG ke database (menghindari model demi
            // kecepatan), sehingga $arsip di memori sudah basi. Tanpa
            // refresh, Eloquent membandingkan nilai baru dengan nilai
            // lamanya yang usang, menyimpulkan "tidak ada yang berubah",
            // dan langkah terakhir tertinggal di sana selamanya.
            $arsip->refresh();

            $arsip->update([
                'path' => $path,
                'ukuran' => Storage::disk('local')->size($path),
                'jumlah_berkas' => $berkas,
                'status' => 'siap',
                'progres' => 100,
                'langkah' => null,
                'catatan' => null,
                'selesai_at' => now(),
            ]);
        } catch (Throwable $e) {
            $arsip->update([
                'status' => 'gagal',
                'catatan' => Str::limit($e->getMessage(), 400),
                'selesai_at' => now(),
            ]);
        } finally {
            $this->hapusFolder($kerja);
        }

        return $arsip->refresh();
    }

    // =================================================================

    private function buatSemuaPdf(string $kerja): int
    {
        $daftarKelas = Kelas::where('tahun_ajaran_id', $this->periode->id)
            ->orderBy('nama_kelas')
            ->get();

        $jumlah = 0;
        $ringkasan = [];

        $semua = DaftarLaporanArsip::semua();

        // +1 untuk langkah terakhir (memampatkan jadi ZIP), supaya batang
        // kemajuan tidak melompat ke 100% padahal berkasnya belum jadi.
        $totalLangkah = count($semua) + 1;
        $ke = 0;

        foreach ($semua as $laporan) {
            // Dilaporkan SEBELUM dikerjakan, bukan sesudah — supaya yang
            // terbaca Admin adalah "sedang mengerjakan ini", bukan nama
            // laporan yang sebenarnya sudah selesai beberapa detik lalu.
            $this->laporkan(
                (int) round($ke / $totalLangkah * 100),
                $laporan['judul']
            );
            $ke++;

            $folder = $kerja.'/'.$laporan['peran'];
            @mkdir($folder, 0755, true);

            $bagian = $laporan['per'] === 'kelas'
                ? $this->renderPerKelas($laporan, $daftarKelas)
                : $this->renderSekali($laporan);

            if ($bagian === []) {
                $ringkasan[] = [$laporan['peran'], $laporan['judul'], 'KOSONG — tidak ada data'];

                continue;
            }

            $nama = Str::slug($laporan['judul']).'.pdf';
            $this->tulisPdf($bagian, $laporan['judul'], $folder.'/'.$nama);

            $ringkasan[] = [$laporan['peran'], $laporan['judul'], count($bagian).' bagian'];
            $jumlah++;
        }

        $this->tulisRingkasan($kerja, $ringkasan, $jumlah);
        $this->laporkan((int) round($ke / $totalLangkah * 100), 'Memampatkan berkas');

        return $jumlah;
    }

    /**
     * Catat kemajuan agar terlihat Admin di halaman Tahun Ajaran.
     *
     * Ditulis langsung ke database, bukan lewat model, karena pemanggilnya
     * berada di dalam pekerja antrian — proses terpisah dari peramban
     * Admin. Database satu-satunya tempat yang bisa dilihat keduanya.
     *
     * Kegagalan menulis SENGAJA diabaikan: penanda kemajuan hanyalah
     * kenyamanan, dan tidak boleh menggagalkan pembuatan arsip yang
     * sesungguhnya.
     */
    private function laporkan(int $persen, string $langkah): void
    {
        try {
            \Illuminate\Support\Facades\DB::table('arsip_semesters')
                ->where('id', $this->arsipId)
                ->update([
                    'progres' => max(0, min(100, $persen)),
                    'langkah' => Str::limit($langkah, 120),
                    'updated_at' => now(),
                ]);
        } catch (Throwable) {
            // Diabaikan dengan sengaja — lihat catatan di atas.
        }
    }

    /** @return array<string, string> judul bab => HTML */
    private function renderPerKelas(array $laporan, $daftarKelas): array
    {
        $bagian = [];

        foreach ($daftarKelas as $kelas) {
            $html = $this->renderRute($laporan['route'], ['kelas' => $kelas->id], $laporan['query'] ?? []);

            if ($html !== null) {
                $bagian['Kelas '.$kelas->nama_kelas] = $html;
            }
        }

        return $bagian;
    }

    /** @return array<string, string> */
    private function renderSekali(array $laporan): array
    {
        $html = $this->renderRute($laporan['route'], [], $laporan['query'] ?? []);

        return $html === null ? [] : [$laporan['judul'] => $html];
    }

    /**
     * Jalankan satu permintaan internal, kembalikan isi halamannya.
     *
     * Mengembalikan null bila halamannya menolak (403 karena peran, 404
     * karena kelasnya kosong, dsb). Satu laporan yang gagal TIDAK boleh
     * menggagalkan seluruh arsip — lebih baik arsip yang kurang satu bab
     * daripada tidak ada arsip sama sekali.
     */
    private function renderRute(string $rute, array $param = [], array $query = []): ?string
    {
        try {
            $url = route($rute, $param, false);
        } catch (Throwable) {
            return null;
        }

        // Guard harus BENAR-BENAR dimasuki, bukan sekadar ditempelkan ke
        // request. Middleware `auth` memeriksa guard-nya, bukan resolver
        // milik request — tanpa baris ini setiap permintaan internal
        // dijawab pengalihan ke halaman login.
        //
        // Diulang di setiap laporan, bukan sekali di awal: sebagian
        // halaman menyentuh sesi, dan lebih murah memastikan ulang
        // daripada menelusuri laporan mana yang menggeser keadaannya.
        Auth::login($this->sebagai);

        // Guard harus BENAR-BENAR dimasuki, bukan sekadar ditempelkan ke
        // request. Middleware `auth` memeriksa guard-nya, bukan resolver
        // milik request — tanpa baris ini setiap permintaan internal
        // dijawab pengalihan ke halaman login.
        //
        // Diulang di setiap laporan, bukan sekali di awal: sebagian
        // halaman menyentuh sesi, dan lebih murah memastikan ulang
        // daripada menelusuri laporan mana yang menggeser keadaannya.
        Auth::login($this->sebagai);

        $request = Request::create($url, 'GET', $query);
        $request->setUserResolver(fn () => $this->sebagai);

        try {
            $response = app(Kernel::class)->handle($request);
        } catch (Throwable) {
            return null;
        }

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        // Sesi & autentikasi sempat disentuh permintaan di atas; dipulihkan
        // supaya pekerjaan berikutnya tidak mewarisi keadaan yang aneh.
        Auth::setUser($this->sebagai);

        $isi = $response->getContent();

        // Jalur Chrome memakai halaman UTUH beserta <head>-nya, supaya CSS
        // aplikasi — termasuk aturan @media print yang memunculkan KOP
        // surat — benar-benar terpakai. Pembersihan hanya dilakukan untuk
        // jalur cadangan mPDF, yang tidak memahami CSS itu.
        return $this->pakaiChrome
            ? $this->siapkanUntukChrome($isi)
            : $this->bersihkan($isi);
    }

    /**
     * Ubah halaman agar bisa dibuka Chrome dari berkas lokal.
     *
     * Dua hal yang harus dikerjakan:
     *
     * 1. Rujukan aset masih berupa `http://localhost/...` — alamat yang
     *    tidak berarti apa-apa bagi Chrome yang membuka berkas dari disk.
     *    Semuanya diarahkan ke berkas sungguhan di folder `public`.
     *
     * 2. JavaScript dibuang. Halaman ini tidak akan disentuh siapa pun;
     *    memuat Alpine dan Livewire hanya memperlambat, dan `x-cloak`
     *    yang tidak pernah dilepas justru MENYEMBUNYIKAN sebagian isi.
     */
    private function siapkanUntukChrome(string $html): string
    {
        $publik = str_replace('\\', '/', public_path());

        $html = preg_replace(
            '#(href|src)="https?://[^/"]+/#i',
            '$1="file:///'.$publik.'/',
            $html
        ) ?? $html;

        $html = $this->sisipkanCss($html, $publik);

        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html) ?? $html;

        // x-cloak menyembunyikan elemen sampai Alpine melepasnya. Karena
        // Alpine sudah dibuang, aturannya harus ikut dimatikan — kalau
        // tidak, seluruh bagian yang memakainya hilang dari cetakan.
        //
        // `cetak-saja` dipaksa tampil dengan alasan yang sama seperti
        // tombol Cetak: di situlah KOP surat berada.
        $tambahan = '<style>'
            .'[x-cloak]{display:revert !important}'
            .'.cetak-saja{display:block !important}'
            .'aside,header.sticky,.no-print{display:none !important}'
            .'@page{size:A4;margin:12mm 10mm}'
            .'</style>';

        return str_ireplace('</head>', $tambahan.'</head>', $html);
    }

    /**
     * Salin isi berkas CSS ke dalam halaman, sambil memutlakkan jalur
     * fonta di dalamnya.
     *
     * KENAPA TIDAK CUKUP MENUNJUK BERKASNYA SAJA
     * ------------------------------------------
     * Berkas CSS hasil bundel merujuk fontanya dengan jalur dari AKAR
     * SITUS, misalnya `url(/build/assets/fa-solid-900.woff2)`. Di peramban
     * biasa itu benar. Tetapi halaman ini dibuka Chrome dari DISK, dan di
     * sana "akar" berarti akar drive — sehingga jalur tadi diartikan
     * `file:///C:/build/assets/...`, yang tidak ada.
     *
     * Akibatnya halus dan mudah terlewat: tata letak, tabel, dan KOP surat
     * semuanya tetap benar, tetapi seluruh tulisan jatuh ke fonta bawaan
     * (Arial/Times) dan setiap ikon Font Awesome berubah menjadi kotak
     * kosong. Dokumennya "jadi" — hanya tidak sama dengan tombol Cetak.
     *
     * Maka isi CSS-nya disalin masuk, dan tiap `url(/...)` diarahkan ke
     * berkas sungguhan di folder `public`.
     */
    private function sisipkanCss(string $html, string $publik): string
    {
        return preg_replace_callback(
            '#<link\b[^>]*\bstylesheet\b[^>]*>#i',
            function (array $cocok) use ($publik): string {
                if (! preg_match('#href="file:///([^"]+)"#i', $cocok[0], $h)) {
                    return $cocok[0];
                }

                if (! is_file($h[1]) || ($css = file_get_contents($h[1])) === false) {
                    return $cocok[0];
                }

                // Hanya jalur dari akar yang diubah. `url(data:...)` dan
                // alamat penuh dibiarkan apa adanya.
                $css = preg_replace(
                    '#url\(\s*([\'"]?)/(?!/)#i',
                    'url($1file:///'.$publik.'/',
                    $css
                ) ?? $css;

                return '<style>'.$css.'</style>';
            },
            $html
        ) ?? $html;
    }

    /**
     * Ambil hanya isi halamannya, buang yang tidak ada artinya di kertas.
     */
    private function bersihkan(string $html): string
    {
        // Sidebar, tombol, penyaring — semuanya sudah ditandai halaman itu
        // sendiri dengan `no-print`, penanda yang sama yang dipakai tombol
        // Cetak di peramban.
        $html = preg_replace('/<(aside|nav|script|style|form)\b[^>]*>.*?<\/\1>/is', '', $html) ?? $html;
        $html = preg_replace('/<[^>]*class="[^"]*\bno-print\b[^"]*"[^>]*>.*?<\/\w+>/is', '', $html) ?? $html;

        // Ambil isi <main> bila ada; itulah badan laporannya.
        if (preg_match('/<main\b[^>]*>(.*?)<\/main>/is', $html, $m)) {
            $html = $m[1];
        } elseif (preg_match('/<body\b[^>]*>(.*?)<\/body>/is', $html, $m)) {
            $html = $m[1];
        }

        return trim($html);
    }

    /**
     * Susun beberapa bab menjadi satu PDF.
     *
     * Gaya cetaknya sengaja sederhana: mPDF bukan peramban dan hanya
     * memahami sebagian kecil CSS. Yang dijamin di sini adalah tabel yang
     * terbaca dan halaman yang terpotong di tempat yang benar — bukan
     * tampilan yang sama persis dengan layar.
     */
    /**
     * Tulis satu PDF dari beberapa bab.
     *
     * Dua jalur, dan bedanya besar:
     *
     *   Chrome — tiap bab dicetak jadi PDF tersendiri lalu digabung.
     *            Hasilnya IDENTIK dengan tombol Cetak, karena mesin yang
     *            mengerjakannya memang sama.
     *   mPDF   — jalur cadangan bila peramban tidak ada di server.
     *            Jadi, tapi sederhana: tanpa KOP surat dan tanpa gaya.
     */
    private function tulisPdf(array $bagian, string $judul, string $tujuan): void
    {
        if ($this->pakaiChrome && $this->tulisPdfChrome($bagian, $tujuan)) {
            return;
        }

        $this->tulisPdfMpdf($bagian, $judul, $tujuan);
    }

    private function tulisPdfChrome(array $bagian, string $tujuan): bool
    {
        $temp = storage_path('app/private/chrome-temp');
        @mkdir($temp, 0755, true);

        $potongan = [];

        foreach (array_values($bagian) as $i => $html) {
            $htmlFile = $temp.'/bab-'.uniqid().'-'.$i.'.html';
            $pdfFile = $temp.'/bab-'.uniqid().'-'.$i.'.pdf';

            file_put_contents($htmlFile, $html);

            if (PencetakChrome::cetak($htmlFile, $pdfFile)) {
                $potongan[] = $pdfFile;
            }

            @unlink($htmlFile);
        }

        if ($potongan === []) {
            return false;
        }

        $berhasil = $this->gabungPdf($potongan, $tujuan);

        foreach ($potongan as $p) {
            @unlink($p);
        }

        return $berhasil;
    }

    /**
     * Gabungkan beberapa PDF menjadi satu.
     *
     * Memakai FPDI, yang sudah ikut terpasang bersama mPDF — jadi tidak
     * ada kebergantungan baru hanya demi penggabungan ini.
     */
    private function gabungPdf(array $daftar, string $tujuan): bool
    {
        if (count($daftar) === 1) {
            return copy($daftar[0], $tujuan);
        }

        try {
            $pdf = new \setasign\Fpdi\Fpdi;

            foreach ($daftar as $berkas) {
                $jumlah = $pdf->setSourceFile($berkas);

                for ($h = 1; $h <= $jumlah; $h++) {
                    $tpl = $pdf->importPage($h);
                    $ukuran = $pdf->getTemplateSize($tpl);

                    $pdf->AddPage($ukuran['orientation'], [$ukuran['width'], $ukuran['height']]);
                    $pdf->useTemplate($tpl);
                }
            }

            $pdf->Output($tujuan, 'F');

            return is_file($tujuan);
        } catch (Throwable) {
            // Penggabungan gagal — lebih baik menyerahkan bab pertama saja
            // daripada tidak ada berkas sama sekali.
            return copy($daftar[0], $tujuan);
        }
    }

    private function tulisPdfMpdf(array $bagian, string $judul, string $tujuan): void
    {
        $pdf = new Mpdf([
            'tempDir' => storage_path('app/private/mpdf-temp'),
            'format' => 'A4',
            'margin_top' => 14,
            'margin_bottom' => 14,
            'margin_left' => 10,
            'margin_right' => 10,
            'default_font_size' => 8,
        ]);

        $pdf->SetTitle($judul);
        $pdf->SetHTMLFooter(
            '<div style="text-align:center;font-size:7pt;color:#888;border-top:0.5pt solid #ccc;padding-top:3px;">'
            .e($judul).' — '.e($this->periode->labelPeriode())
            .' &nbsp;·&nbsp; halaman {PAGENO} dari {nbpg}</div>'
        );

        $pdf->WriteHTML($this->gayaCetak(), \Mpdf\HTMLParserMode::HEADER_CSS);

        $pertama = true;
        foreach ($bagian as $namaBab => $html) {
            if (! $pertama) {
                $pdf->AddPage();
            }
            $pertama = false;

            $pdf->WriteHTML(
                '<h2 class="bab">'.e($namaBab).'</h2>'.$html,
                \Mpdf\HTMLParserMode::HTML_BODY
            );
        }

        file_put_contents($tujuan, $pdf->Output('', 'S'));
    }

    private function gayaCetak(): string
    {
        return <<<'CSS'
        body { font-family: sans-serif; font-size: 8pt; color: #1e293b; }
        h1, h2, h3 { color: #0f172a; margin: 0 0 6px; }
        h2.bab { font-size: 13pt; border-bottom: 1pt solid #1c68f2; padding-bottom: 4px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 6px 0 12px; }
        th, td { border: 0.5pt solid #cbd5e1; padding: 3px 5px; text-align: left; vertical-align: top; }
        th { background: #eef2f7; font-weight: bold; }
        p { margin: 4px 0; }
        CSS;
    }

    /**
     * Halaman pertama ZIP: apa saja yang ada di dalamnya, dan yang KOSONG.
     *
     * Yang kosong sengaja ikut dicantumkan. Arsip yang diam-diam kurang
     * satu laporan jauh lebih berbahaya daripada arsip yang terang-terangan
     * menyebutkan mana yang tidak berisi.
     */
    private function tulisRingkasan(string $kerja, array $baris, int $jumlah): void
    {
        $html = '<h1>Arsip Semester</h1>'
            .'<p><strong>'.e($this->periode->labelPeriode()).'</strong></p>'
            .'<p>Dibuat: '.now()->translatedFormat('l, d F Y H:i').'</p>'
            .'<p>Berisi '.$jumlah.' berkas laporan.</p>'
            .'<table><tr><th>Bagian</th><th>Laporan</th><th>Isi</th></tr>';

        foreach ($baris as [$peran, $judul, $isi]) {
            $html .= '<tr><td>'.e(DaftarLaporanArsip::labelPeran($peran)).'</td>'
                .'<td>'.e($judul).'</td><td>'.e($isi).'</td></tr>';
        }

        $html .= '</table>'
            .'<p style="margin-top:14px;font-size:7pt;color:#64748b;">'
            .'Berkas ini dibuat otomatis oleh SIM-SPENGA. Isinya adalah salinan laporan '
            .'pada saat arsip dibuat. Bila semester ini kelak dibuka kunci dan datanya diubah, '
            .'arsip ini tidak ikut berubah — buatlah arsip baru.</p>';

        // Sengaja LANGSUNG ke mPDF, tidak lewat tulisPdf(). Lembar ini
        // bukan salinan halaman aplikasi melainkan HTML yang disusun di
        // sini, jadi tidak memakai fonta aplikasi — dan pemeriksa mutu di
        // PencetakChrome justru menilainya gagal karena itu. Menyerahkannya
        // ke Chrome hanya membuang dua percobaan sebelum jatuh ke mPDF juga.
        $this->tulisPdfMpdf(['Ringkasan' => $html], 'Ringkasan Arsip', $kerja.'/RINGKASAN.pdf');
    }

    private function bungkusJadiZip(string $kerja): string
    {
        Storage::disk('local')->makeDirectory(self::FOLDER);

        $nama = 'arsip-'.Str::slug($this->periode->nama.'-'.$this->periode->semester)
            .'-'.now()->format('Ymd-Hi').'.zip';
        $path = self::FOLDER.'/'.$nama;
        $penuh = Storage::disk('local')->path($path);

        $zip = new ZipArchive;
        if ($zip->open($penuh, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Tidak bisa membuat berkas ZIP arsip.');
        }

        $iter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($kerja, \FilesystemIterator::SKIP_DOTS));
        foreach ($iter as $f) {
            if (! $f->isFile()) {
                continue;
            }

            // Nama di dalam ZIP WAJIB memakai garis miring biasa, apa pun
            // sistem yang membuatnya. Dibangun di Windows, DIRECTORY_SEPARATOR
            // menghasilkan "wali-kelas\laporan.pdf" — yang di Linux terbaca
            // sebagai SATU berkas bernama aneh, bukan folder berisi berkas.
            $namaDalamZip = str_replace(
                '\\',
                '/',
                substr($f->getPathname(), strlen($kerja) + 1)
            );

            $zip->addFile($f->getPathname(), $namaDalamZip);
        }

        $zip->close();

        return $path;
    }

    private function hapusFolder(string $folder): void
    {
        if (! is_dir($folder)) {
            return;
        }

        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($folder, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iter as $f) {
            $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        }

        @rmdir($folder);
    }
}
