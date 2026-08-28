<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * "Baris ini masih dipakai apa saja?"
 *
 * Dipakai penjaga hapus (Controller::hapusAtauGagalDenganPesan) supaya
 * pesan penolakannya menyebut ANGKA dan NAMA yang dimengerti operator —
 * "masih dipakai 12 jadwal pelajaran dan 340 nilai siswa" — bukan sekadar
 * "masih memiliki data terkait" yang tidak memberi petunjuk apa pun.
 *
 * Cara kerjanya membaca daftar foreign key dari information_schema, jadi
 * otomatis ikut kalau ada tabel baru yang menunjuk ke sini — tidak perlu
 * ada daftar manual yang harus diingat untuk diperbarui.
 */
class PemakaiData
{
    /**
     * Nama tabel dalam bahasa yang dipakai sehari-hari di sekolah.
     * Tabel yang tidak terdaftar dipakai apa adanya (garis bawah jadi spasi).
     */
    private const NAMA_TABEL = [
        'absensi_ekskul_pesertas' => 'kehadiran ekstrakurikuler',
        'absensi_ekskuls' => 'sesi absensi ekstrakurikuler',
        'absensi_kegiatans' => 'absensi kegiatan sekolah',
        'absensi_siswas' => 'baris absensi siswa',
        'analisis_sumatifs' => 'lembar analisis sumatif',
        'ekstrakurikuler_pembinas' => 'pembina ekstrakurikuler',
        'ekstrakurikuler_siswas' => 'anggota ekstrakurikuler',
        'evaluasi_pembinaans' => 'evaluasi pembinaan BK',
        'guru_bk_kelas' => 'mapping guru BK',
        'guru_mengajar_kelas' => 'mapping guru mengajar',
        'jadwal_pelajarans' => 'jadwal pelajaran',
        'jurnal_mengajars' => 'jurnal mengajar',
        'kasus_siswas' => 'kasus BK',
        'kegiatan_sekolahs' => 'kegiatan sekolah',
        'kelas' => 'kelas',
        'kktp_tingkats' => 'pengaturan KKTP',
        'mata_pelajarans' => 'mata pelajaran',
        'nilai_siswas' => 'nilai siswa',
        'orang_tuas' => 'akun portal orang tua',
        'pemanggilan_orangtuas' => 'pemanggilan orang tua',
        'pembinaan_siswas' => 'pembinaan BK',
        'pengurangan_poin_siswas' => 'pengurangan poin BK',
        'penilaian_kelas_mapels' => 'kepala lembar penilaian',
        'penugasan_wali_kelas' => 'penugasan wali kelas',
        'riwayat_kelas_siswas' => 'riwayat kelas siswa',
        'siswas' => 'siswa',
        'surats' => 'surat',
    ];

    /** Cache daftar FK per tabel induk — satu query untuk seluruh request. */
    private static array $cacheFk = [];

    /**
     * Rincian pemakaian sebuah baris, mis. ['jadwal pelajaran' => 12].
     * Hanya menghitung relasi yang MENGHALANGI penghapusan (RESTRICT /
     * NO ACTION) — yang CASCADE atau SET NULL memang ikut terurus sendiri
     * dan tidak perlu dilaporkan ke pengguna.
     *
     * @return array<string, int>
     */
    public static function rincian(Model $model): array
    {
        $tabel = $model->getTable();
        $kunci = $model->getKeyName();
        $nilai = $model->getKey();

        $hasil = [];

        foreach (self::daftarFk($tabel) as $fk) {
            if (! in_array($fk->aturan, ['RESTRICT', 'NO ACTION'], true)) {
                continue;
            }
            if ($fk->kolomInduk !== $kunci) {
                continue;
            }

            try {
                $jumlah = DB::table($fk->anak)->where($fk->kolom, $nilai)->count();
            } catch (\Throwable) {
                continue;
            }

            if ($jumlah > 0) {
                $label = self::NAMA_TABEL[$fk->anak] ?? str_replace('_', ' ', $fk->anak);
                $hasil[$label] = ($hasil[$label] ?? 0) + $jumlah;
            }
        }

        arsort($hasil);

        return $hasil;
    }

    /**
     * Kalimat siap pakai, mis. "12 jadwal pelajaran dan 340 nilai siswa".
     * Mengembalikan null kalau ternyata tidak ada yang memakai.
     */
    public static function kalimat(Model $model): ?string
    {
        $rincian = self::rincian($model);

        if ($rincian === []) {
            return null;
        }

        $bagian = [];
        foreach ($rincian as $label => $jumlah) {
            $bagian[] = $jumlah.' '.$label;
        }

        if (count($bagian) === 1) {
            return $bagian[0];
        }

        $terakhir = array_pop($bagian);

        return implode(', ', $bagian).' dan '.$terakhir;
    }

    /** @return list<object> */
    private static function daftarFk(string $tabelInduk): array
    {
        if (isset(self::$cacheFk[$tabelInduk])) {
            return self::$cacheFk[$tabelInduk];
        }

        return self::$cacheFk[$tabelInduk] = DB::select(
            'SELECT rc.TABLE_NAME anak, k.COLUMN_NAME kolom,
                    k.REFERENCED_COLUMN_NAME kolomInduk, rc.DELETE_RULE aturan
               FROM information_schema.REFERENTIAL_CONSTRAINTS rc
               JOIN information_schema.KEY_COLUMN_USAGE k
                 ON k.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
                AND k.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA
                AND k.TABLE_NAME = rc.TABLE_NAME
              WHERE rc.CONSTRAINT_SCHEMA = DATABASE()
                AND rc.REFERENCED_TABLE_NAME = ?',
            [$tabelInduk]
        );
    }

    public static function lupakanCache(): void
    {
        self::$cacheFk = [];
    }
}
