<?php

namespace App\Support;

use App\Models\GuruBkKelas;
use App\Models\Kelas;
use App\Models\KasusSiswa;
use App\Models\Pemberitahuan;
use App\Models\PemanggilanOrangTua;
use App\Models\PrestasiSiswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * SATU PINTU untuk mengirim pemberitahuan ke lonceng.
 *
 * =====================================================================
 * KENAPA TERPUSAT
 * =====================================================================
 * Menentukan SIAPA yang perlu tahu adalah bagian yang paling mudah
 * salah, dan aturannya sama di beberapa tempat: guru BK kelas itu, wali
 * kelasnya, Kesiswaan. Kalau ditulis ulang di tiap controller, satu
 * tempat yang terlewat berarti ada orang yang tidak pernah diberi tahu —
 * dan tidak ada galat apa pun yang menandainya.
 *
 * =====================================================================
 * KEGAGALAN DI SINI TIDAK BOLEH MENGGAGALKAN PEKERJAAN UTAMA
 * =====================================================================
 * Guru yang mencatat kasus siswa tidak boleh kehilangan catatannya hanya
 * karena pengiriman pemberitahuan bermasalah. Karena itu seluruh
 * pengiriman dibungkus try/catch: galatnya dicatat ke log, tetapi
 * catatan kasusnya tetap tersimpan.
 */
class KirimPemberitahuan
{
    /**
     * Kirim satu pemberitahuan ke banyak orang sekaligus.
     *
     * @param  iterable<int>  $penerima  id pengguna
     */
    public static function kirim(
        iterable $penerima,
        string $jenis,
        string $judul,
        string $isi,
        ?string $tautan = null,
        ?int $dariId = null,
    ): int {
        try {
            $dariId ??= auth()->id();

            $ids = collect($penerima)
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                // Pengirimnya sendiri tidak perlu diberi tahu atas
                // perbuatannya sendiri.
                ->reject(fn ($id) => $id === $dariId)
                ->values();

            if ($ids->isEmpty()) {
                return 0;
            }

            $sekarang = now();

            $baris = $ids->map(fn ($id) => [
                'untuk_user_id' => $id,
                'dari_user_id' => $dariId,
                'jenis' => $jenis,
                'judul' => Str::limit($judul, 250, ''),
                'isi' => Str::limit($isi, 290),
                'tautan' => $tautan,
                'dibaca_at' => null,
                'created_at' => $sekarang,
                'updated_at' => $sekarang,
            ])->all();

            // Satu insert untuk semua penerima, bukan satu per orang.
            DB::table('pemberitahuans')->insert($baris);

            return count($baris);
        } catch (Throwable $e) {
            report($e);

            return 0;
        }
    }

    // =================================================================
    // Penerima menurut perannya
    // =================================================================

    /** Seluruh pengguna AKTIF dengan peran tertentu. */
    private static function berperan(array $peran): \Illuminate\Support\Collection
    {
        return User::whereIn('role', $peran)->where('is_active', true)->pluck('id');
    }

    /** Guru BK yang membina kelas ini pada periode aktif. */
    private static function guruBkKelas(?int $kelasId): \Illuminate\Support\Collection
    {
        if (! $kelasId) {
            return collect();
        }

        return GuruBkKelas::where('kelas_id', $kelasId)
            ->when(TahunAjaran::aktif(), fn ($q, $t) => $q->where('tahun_ajaran_id', $t->id))
            ->pluck('guru_id');
    }

    /** Wali kelas dari kelas ini. */
    private static function waliKelas(?int $kelasId): \Illuminate\Support\Collection
    {
        if (! $kelasId) {
            return collect();
        }

        $wali = Kelas::find($kelasId)?->waliKelas;

        return $wali ? collect([$wali->id]) : collect();
    }

    // =================================================================
    // Kejadian
    // =================================================================

    /**
     * Kasus siswa baru dicatat.
     *
     * Yang perlu tahu: guru BK kelas itu (yang menanganinya), wali
     * kelasnya (yang bertanggung jawab atas kelasnya), dan Kesiswaan
     * (yang memantau menyeluruh).
     */
    public static function kasusSiswa(KasusSiswa $kasus): int
    {
        $kasus->loadMissing(['siswa', 'kelas']);

        $nama = $kasus->siswa?->nama ?? 'Siswa';
        $kelas = $kasus->kelas?->nama_kelas;

        return self::kirim(
            self::guruBkKelas($kasus->kelas_id)
                ->merge(self::waliKelas($kasus->kelas_id))
                ->merge(self::berperan(['kesiswaan'])),
            'kasus_siswa',
            'Laporan kasus siswa baru',
            trim($nama.($kelas ? ' ('.$kelas.')' : '').' — '.$kasus->nama_pelanggaran
                .' · '.$kasus->poin.' poin'),
            route('bk.kasus.index', [], false),
        );
    }

    /**
     * Prestasi dicatat wali kelas dan MENUNGGU diperiksa Kesiswaan.
     *
     * Hanya dikirim bila belum terverifikasi. Prestasi yang dicatat
     * Kesiswaan sendiri sudah langsung sah, jadi tidak ada yang perlu
     * diberi tahu.
     */
    public static function prestasiMenunggu(PrestasiSiswa $prestasi): int
    {
        if ($prestasi->diverifikasi_at !== null) {
            return 0;
        }

        $prestasi->loadMissing('siswa');

        return self::kirim(
            self::berperan(['kesiswaan']),
            'prestasi_menunggu',
            'Prestasi menunggu verifikasi',
            ($prestasi->siswa?->nama ?? 'Siswa').' — '.$prestasi->nama,
            route('prestasi.index', [], false),
        );
    }

    /** Kesiswaan sudah memverifikasi — pencatatnya perlu tahu. */
    public static function prestasiTerverifikasi(PrestasiSiswa $prestasi): int
    {
        if (! $prestasi->dicatat_oleh) {
            return 0;
        }

        $prestasi->loadMissing('siswa');

        return self::kirim(
            [$prestasi->dicatat_oleh],
            'prestasi_terverifikasi',
            'Prestasi sudah diverifikasi',
            ($prestasi->siswa?->nama ?? 'Siswa').' — '.$prestasi->nama,
            route('prestasi.index', [], false),
        );
    }

    /**
     * Pemanggilan orang tua dicatat guru BK.
     *
     * Wali kelasnya WAJIB tahu: orang tua anak kelasnya dipanggil ke
     * sekolah, dan ia yang akan ditanyai bila orang tua menghubunginya
     * lebih dulu. Kesiswaan ikut karena memantau menyeluruh.
     *
     * Tautannya langsung ke halaman siswa itu, bukan ke daftar — di sana
     * riwayat kasus, pembinaan, dan pemanggilannya tampil sekaligus.
     */
    public static function pemanggilanOrangTua(PemanggilanOrangTua $pemanggilan): int
    {
        $pemanggilan->loadMissing('siswa');
        $siswa = $pemanggilan->siswa;

        return self::kirim(
            self::waliKelas($siswa?->kelasIdSekarang())
                ->merge(self::berperan(['kesiswaan'])),
            'pemanggilan_orangtua',
            'Pemanggilan orang tua dicatat',
            ($siswa?->nama ?? 'Siswa').' — '.Str::limit($pemanggilan->alasan, 150),
            $siswa ? route('bk.siswa.show', $siswa->id, false) : route('bk.pemanggilan.index', [], false),
        );
    }

    /**
     * Guru mata pelajaran memfinalisasi daftar nilai.
     *
     * Dua penerima dengan kebutuhan BERBEDA, jadi tautannya pun berbeda:
     *
     *   Kurikulum  -> Monitoring Input Nilai, untuk melihat mana yang
     *                 masih tertinggal dari seluruh sekolah.
     *   Wali kelas -> Nilai Rapor Kelas, karena nilai itu baru saja
     *                 masuk ke rapor kelasnya.
     *
     * Mengirim satu tautan untuk keduanya akan membuat salah satu
     * mendarat di halaman yang bukan urusannya.
     */
    public static function nilaiDifinalisasi(Kelas $kelas, string $namaMapel): int
    {
        $isi = $kelas->nama_kelas.' — '.$namaMapel;

        $jumlah = self::kirim(
            self::berperan(['kurikulum']),
            'nilai_final',
            'Daftar nilai difinalisasi',
            $isi,
            route('nilai.monitoring', [], false),
        );

        $jumlah += self::kirim(
            self::waliKelas($kelas->id),
            'nilai_final',
            'Nilai masuk ke rapor kelas Anda',
            $isi,
            route('nilai.rekap-kelas', [], false),
        );

        return $jumlah;
    }

    /**
     * Guru BK menerbitkan surat untuk seorang siswa.
     *
     * Wali kelasnya diberi tahu. Orang tua sering menghubungi wali kelas
     * lebih dulu — dan sebelum ini wali kelas tidak tahu apa-apa.
     *
     * Tautannya langsung ke SURATNYA. Sejak wali kelas diizinkan membaca
     * surat anak kelasnya (lihat SuratController::show), mengantarnya ke
     * halaman siswa justru menambah satu klik tanpa alasan.
     */
    public static function suratUntukSiswa(\App\Models\Surat $surat): int
    {
        $surat->loadMissing(['siswa', 'jenisSurat']);
        $siswa = $surat->siswa;

        if (! $siswa) {
            return 0;
        }

        return self::kirim(
            self::waliKelas($siswa->kelasIdSekarang()),
            'surat_siswa',
            'Surat diterbitkan untuk siswa kelas Anda',
            trim($siswa->nama.' — '.($surat->jenisSurat?->nama_jenis ?? 'Surat')
                .($surat->nomor_surat ? ' · No. '.$surat->nomor_surat : '')),
            route('surat.show', $surat->id, false),
        );
    }

    /*
     * TIDAK ADA PEMBERITAHUAN UNTUK DISPOSISI SURAT.
     *
     * Sempat dibuat, lalu dicabut lagi setelah ketahuan bahwa fitur
     * Disposisi SUDAH TIDAK DIPAKAI: pada perombakan alur surat
     * 26 Agustus 2026 ia dikeluarkan dari spesifikasi, dan
     * DisposisiSuratController kini tidak punya satu pun rute yang
     * menunjuk kepadanya (tabel dan controllernya sengaja ditinggal
     * supaya data lama tidak hilang — lihat catatan di routes/web.php
     * pada blok SURAT).
     *
     * Memasang kait pada kode yang tidak pernah dijalankan hanya
     * menyesatkan pembaca berikutnya: ia akan mengira fitur itu hidup.
     *
     * Kalau kelak surat perlu memberi tahu seseorang, yang masuk akal
     * adalah memberi tahu WALI KELAS ketika guru BK menerbitkan surat
     * untuk anak kelasnya — bukan menghidupkan kembali disposisi.
     */
}
