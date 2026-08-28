<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PENUGASAN WALI KELAS — satu baris = "kelas X diwalikan guru Y pada
 * semester Z".
 *
 * Dulu cukup kolom `kelas.wali_kelas_id`. Masalahnya baris kelas dipakai
 * bersama oleh Semester Ganjil & Genap (kelas melekat pada TAHUN ajaran),
 * sehingga mengganti wali kelas di Semester 2 — karena gurunya pensiun,
 * mutasi, atau bertukar tugas — ikut mengubah Semester 1 yang sudah lewat.
 * Rapor & rekap Semester 1 yang dicetak ulang jadi menyebut nama yang
 * salah.
 *
 * Bentuknya sengaja dibuat sama persis dengan GuruBkKelas: tahun_ajaran_id
 * menunjuk baris SEMESTER-nya sendiri, bukan baris Ganjil.
 *
 * Yang membaca tabel ini sehari-hari bukan model ini langsung, melainkan
 * dua relasi yang sudah menyaring periode aktif:
 *   - Kelas::waliKelas()  -> siapa wali kelas ini sekarang
 *   - User::kelasWali()   -> guru ini wali kelas mana sekarang
 */
class PenugasanWaliKelas extends Model
{
    protected $table = 'penugasan_wali_kelas';

    protected $fillable = ['tahun_ajaran_id', 'kelas_id', 'guru_id'];

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    /**
     * Tetapkan wali kelas untuk SATU kelas pada satu semester.
     *
     * Dipakai form Data Kelas, Import Excel Kelas, dan Salin Data —
     * ketiganya lewat pintu yang sama supaya aturannya tidak bercabang.
     * $guruId null berarti "kosongkan wali kelasnya".
     */
    public static function tetapkan(int $kelasId, ?int $guruId, ?TahunAjaran $periode): void
    {
        if (! $periode) {
            return;
        }

        if (! $guruId) {
            static::where('tahun_ajaran_id', $periode->id)->where('kelas_id', $kelasId)->delete();

            return;
        }

        static::updateOrCreate(
            ['tahun_ajaran_id' => $periode->id, 'kelas_id' => $kelasId],
            ['guru_id' => $guruId]
        );
    }

    /**
     * Tetapkan wali kelas lewat form Data Kelas, yang tidak menanyakan
     * semester. Aturannya:
     *
     * - Kalau kelas ini milik TAHUN AJARAN YANG SEDANG AKTIF, perubahan
     *   HANYA berlaku untuk semester yang aktif. Inilah inti perubahan
     *   28 Agustus 2026: mengganti wali kelas di Semester 2 tidak boleh
     *   ikut mengubah Semester 1 yang sudah lewat.
     *
     * - Kalau kelas ini milik tahun ajaran LAIN (biasanya tahun depan yang
     *   sedang disiapkan), belum ada "semester berjalan" yang bisa jadi
     *   acuan — jadi ditetapkan untuk KEDUA semesternya sekaligus, dan
     *   nanti tinggal diubah per semester kalau memang ada pergantian.
     */
    public static function tetapkanLewatFormKelas(int $kelasId, ?int $guruId, ?TahunAjaran $tahunKelas): void
    {
        if (! $tahunKelas) {
            return;
        }

        $aktif = TahunAjaran::aktif();

        if ($aktif && $aktif->nama === $tahunKelas->nama) {
            static::tetapkan($kelasId, $guruId, $aktif);

            return;
        }

        foreach (TahunAjaran::where('nama', $tahunKelas->nama)->get() as $semester) {
            static::tetapkan($kelasId, $guruId, $semester);
        }
    }
}
