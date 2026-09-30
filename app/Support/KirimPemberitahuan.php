<?php

namespace App\Support;

use App\Models\GuruBkKelas;
use App\Models\Kelas;
use App\Models\KasusSiswa;
use App\Models\Pemberitahuan;
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
}
