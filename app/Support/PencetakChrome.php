<?php

namespace App\Support;

use Symfony\Component\Process\Process;

/**
 * MENCETAK HALAMAN MENJADI PDF MEMAKAI PERAMBAN, BUKAN PUSTAKA PDF.
 *
 * =====================================================================
 * KENAPA HARUS PERAMBAN
 * =====================================================================
 * Percobaan pertama memakai mPDF — pustaka PDF murni PHP. Hasilnya jadi,
 * tetapi jauh berbeda dari tombol Cetak di layar: KOP surat hilang,
 * perataan kacau, tabel telanjang. Sebabnya mendasar dan tidak bisa
 * ditambal: mPDF hanya memahami sebagian kecil CSS2 dan sama sekali
 * tidak mengenal flexbox, sedangkan seluruh tampilan aplikasi ini
 * dibangun dengan Tailwind yang bersandar padanya.
 *
 * Yang membuat tombol Cetak menghasilkan dokumen rapi adalah PERAMBAN —
 * ia memahami seluruh CSS, termasuk aturan `@media print` yang justru di
 * situlah KOP surat dimunculkan (lihat kelas `cetak-saja`).
 *
 * Maka jalan yang benar bukan meniru peramban, melainkan MEMAKAINYA.
 * Chrome dan Edge bisa dijalankan tanpa jendela dan diminta mencetak
 * sebuah halaman menjadi PDF. Hasilnya bukan mirip dengan tombol Cetak —
 * hasilnya identik, karena mesin yang mengerjakannya memang sama.
 *
 * =====================================================================
 * KALAU PERAMBANNYA TIDAK ADA DI SERVER
 * =====================================================================
 * Hosting bersama umumnya tidak menyediakan Chromium. Karena itu kelas
 * ini TIDAK PERNAH dipaksakan: pemanggilnya memeriksa tersedia() lebih
 * dulu dan jatuh kembali ke mPDF bila tidak ada. Arsip yang sederhana
 * masih jauh lebih baik daripada tidak ada arsip.
 */
class PencetakChrome
{
    /**
     * Letak yang dicoba berurutan. Edge sengaja ikut: ia selalu ada di
     * Windows dan mesin renderernya sama dengan Chrome (Chromium).
     */
    private const KANDIDAT = [
        // Linux (hosting)
        '/usr/bin/chromium',
        '/usr/bin/chromium-browser',
        '/usr/bin/google-chrome',
        '/usr/bin/google-chrome-stable',
        // Windows
        'C:\Program Files\Google\Chrome\Application\chrome.exe',
        'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe',
        'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe',
        'C:\Program Files\Microsoft\Edge\Application\msedge.exe',
    ];

    private static ?string $cache = null;

    public static function binari(): ?string
    {
        if (self::$cache !== null) {
            return self::$cache ?: null;
        }

        // Setelan .env selalu menang — supaya sekolah yang Chromium-nya
        // berada di tempat tidak lazim cukup menuliskannya, tanpa perlu
        // menyunting kode.
        if ($dari = config('arsip.chrome')) {
            return self::$cache = (is_file($dari) ? $dari : '');
        }

        foreach (self::KANDIDAT as $calon) {
            if (is_file($calon)) {
                return self::$cache = $calon;
            }
        }

        return self::$cache = '' ?: null;
    }

    public static function tersedia(): bool
    {
        return self::binari() !== null;
    }

    /**
     * Cetak satu berkas HTML lokal menjadi PDF.
     *
     * Mengembalikan true bila berhasil. Sengaja tidak melempar galat:
     * satu halaman yang gagal dicetak tidak boleh menggagalkan seluruh
     * arsip — lebih baik arsip yang kurang satu bab daripada tidak ada.
     */
    public static function cetak(string $berkasHtml, string $berkasPdf): bool
    {
        // Dicoba dua kali. Kegagalan Chrome bersifat TIDAK KONSISTEN —
        // gejalanya "kadang jadi, kadang polos", terutama saat belasan
        // halaman dicetak beruntun. Satu percobaan ulang menutup hampir
        // seluruh kejadian itu, dan ongkosnya hanya beberapa detik pada
        // kasus yang memang gagal.
        foreach ([1, 2] as $percobaan) {
            if (self::sekaliCetak($berkasHtml, $berkasPdf)) {
                return true;
            }

            @unlink($berkasPdf);
        }

        return false;
    }

    private static function sekaliCetak(string $berkasHtml, string $berkasPdf): bool
    {
        $chrome = self::binari();

        if (! $chrome) {
            return false;
        }

        // Profil sendiri untuk tiap pencetakan. Tanpa ini, dua proses
        // Chrome yang berdekatan memperebutkan kunci profil bawaan, dan
        // yang kalah keluar diam-diam tanpa menghasilkan apa pun — sebab
        // paling sering dari kegagalan yang tampak acak.
        $profil = sys_get_temp_dir().DIRECTORY_SEPARATOR.'chrome-arsip-'.uniqid();

        $proses = new Process([
            $chrome,
            '--headless=new',
            '--disable-gpu',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--user-data-dir='.$profil,
            // Halaman kita memuat CSS lewat file:// — tanpa izin ini
            // Chrome menolak membacanya dan hasilnya polos tanpa gaya.
            '--allow-file-access-from-files',
            // TANPA BARIS INI, HASILNYA TIDAK BERGAYA — dan itu bukan
            // kegagalan yang terlihat, melainkan PDF yang "jadi" tetapi
            // polos: tanpa KOP, tanpa perataan, gambar kosong.
            //
            // Sebabnya: --print-to-pdf mencetak begitu dokumen dianggap
            // siap, tanpa menunggu berkas CSS, fonta, dan gambar selesai
            // dimuat. Pada halaman kecil biasanya keburu sempat; pada
            // halaman aplikasi ini — 87 KB CSS, beberapa fonta, dua logo —
            // sering tidak. Itu sebabnya gejalanya tampak "kadang jadi,
            // kadang tidak", dan makin sering gagal saat banyak halaman
            // dicetak beruntun.
            //
            // --virtual-time-budget menyuruh Chrome memajukan waktu
            // semunya sampai halaman benar-benar diam, baru mencetak.
            '--virtual-time-budget=20000',
            // Pastikan seluruh tahap penggambaran selesai sebelum hasilnya
            // diambil — pelengkap dari baris di atas.
            '--run-all-compositor-stages-before-draw',

            // Kepala & kaki bawaan peramban (URL, tanggal, nomor halaman)
            // tidak pantas ada di dokumen resmi sekolah.
            '--no-pdf-header-footer',
            '--print-to-pdf-no-header',
            '--print-to-pdf='.$berkasPdf,
            'file:///'.str_replace('\\', '/', $berkasHtml),
        ]);

        // Halaman bertabel panjang butuh waktu; 120 detik sudah lapang
        // untuk satu halaman, dan tetap menjaga agar proses yang macet
        // tidak menggantung seluruh pembuatan arsip.
        $proses->setTimeout(120);

        try {
            $proses->run();
        } catch (\Throwable) {
            self::bersihkanProfil($profil);

            return false;
        }

        self::bersihkanProfil($profil);

        return self::bergaya($berkasPdf);
    }

    /**
     * Apakah PDF ini benar-benar bergaya, bukan sekadar "jadi"?
     *
     * Kegagalan yang paling berbahaya bukan PDF yang tidak terbentuk —
     * itu ketahuan seketika. Yang berbahaya adalah PDF yang terbentuk
     * TETAPI POLOS, karena Chrome mencetak sebelum CSS, fonta, dan
     * gambarnya sempat dimuat. Berkasnya ada, ukurannya wajar, dan tidak
     * ada satu pun tanda galat — baru ketahuan saat dibuka manusia.
     *
     * Penandanya: NAMA fonta yang tertanam. Seluruh halaman aplikasi ini
     * memakai Plus Jakarta Sans, jadi PDF yang CSS-nya benar-benar
     * terpakai pasti menyebutnya. Yang polos jatuh ke fonta bawaan
     * sistem — Arial dan Times New Roman.
     *
     * Pemeriksaan ini pernah salah dengan hanya mencari kata "FontFile":
     * Arial pun ditanamkan sebagai FontFile, sehingga halaman yang gagal
     * bergaya tetap dinyatakan lulus. Nama fontanyalah yang membedakan.
     */
    private static function bergaya(string $berkasPdf): bool
    {
        if (! is_file($berkasPdf) || filesize($berkasPdf) < 1000) {
            return false;
        }

        $isi = file_get_contents($berkasPdf);

        return $isi !== false && str_contains($isi, 'PlusJakartaSans');
    }

    private static function bersihkanProfil(string $folder): void
    {
        if (! is_dir($folder)) {
            return;
        }

        try {
            $iter = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($folder, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iter as $f) {
                $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
            }

            @rmdir($folder);
        } catch (\Throwable) {
            // Profil sementara yang tertinggal tidak merusak apa pun;
            // sistem akan membersihkan folder temp-nya sendiri.
        }
    }
}
