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

    public function __construct(
        private readonly TahunAjaran $periode,
        private readonly User $sebagai,
    ) {
    }

    /**
     * Kerjakan seluruhnya. Mengembalikan ArsipSemester yang sudah terisi.
     */
    public function jalankan(ArsipSemester $arsip): ArsipSemester
    {
        $kerja = storage_path('app/private/arsip-sementara-'.uniqid());
        @mkdir($kerja, 0755, true);

        try {
            $berkas = $this->buatSemuaPdf($kerja);

            if ($berkas === 0) {
                throw new \RuntimeException('Tidak ada satu pun laporan yang berhasil dirender.');
            }

            $path = $this->bungkusJadiZip($kerja);

            $arsip->update([
                'path' => $path,
                'ukuran' => Storage::disk('local')->size($path),
                'jumlah_berkas' => $berkas,
                'status' => 'siap',
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

        foreach (DaftarLaporanArsip::semua() as $laporan) {
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

        return $jumlah;
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

        return $this->bersihkan($response->getContent());
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
    private function tulisPdf(array $bagian, string $judul, string $tujuan): void
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

        $this->tulisPdf(['Ringkasan' => $html], 'Ringkasan Arsip', $kerja.'/RINGKASAN.pdf');
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
