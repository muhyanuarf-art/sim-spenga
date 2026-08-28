<?php

namespace App\Models;

use App\Support\KonteksPeriode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * KEANGGOTAAN KELAS — satu baris = "siswa X anggota kelas Y pada semester Z".
 *
 * Dulu cukup kolom `siswas.kelas_id`. Masalahnya penunjuk itu cuma satu:
 * begitu siswa dipindahkan ke kelas semester berikutnya, daftar siswa
 * SEMESTER LAMA ikut berubah — wali kelas Semester 1 yang menengok
 * periodenya akan melihat daftar siswa Semester 2. Sekarang tiap semester
 * punya barisnya sendiri sehingga daftar lama tidak pernah bergeser.
 *
 * Indeks unik (tahun_ajaran_id, siswa_id) menjamin di tingkat DATABASE
 * bahwa satu siswa hanya berada di satu kelas per semester — bukan cuma
 * disiplin di kode.
 *
 * Yang membaca tabel ini sehari-hari bukan model ini langsung, melainkan
 * relasi & scope yang sudah menyaring periode:
 *   - Siswa::kelas()       -> kelas siswa ini pada periode yang dilihat
 *   - Kelas::siswas()      -> anggota kelas ini
 *   - Siswa::diKelas($id)  -> penyaring daftar siswa per kelas
 */
class AnggotaKelas extends Model
{
    protected $table = 'anggota_kelas';

    protected $fillable = ['tahun_ajaran_id', 'siswa_id', 'kelas_id'];

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    /**
     * Tempatkan seorang siswa di sebuah kelas. Semesternya diambil dari
     * kelas itu sendiri, jadi tidak mungkin meleset.
     *
     * Dipakai form Data Siswa, Import Excel, Pindah Kelas, dan Salin Data —
     * semuanya lewat pintu yang sama supaya aturannya tidak bercabang.
     */
    public static function tempatkan(int $siswaId, Kelas $kelas): void
    {
        static::updateOrCreate(
            ['tahun_ajaran_id' => $kelas->tahun_ajaran_id, 'siswa_id' => $siswaId],
            ['kelas_id' => $kelas->id]
        );
    }

    /** Keluarkan siswa dari kelasnya pada satu semester. */
    public static function keluarkan(int $siswaId, ?TahunAjaran $periode = null): void
    {
        $periode ??= KonteksPeriode::pilihan();

        if ($periode) {
            static::where('tahun_ajaran_id', $periode->id)->where('siswa_id', $siswaId)->delete();
        }
    }
}
