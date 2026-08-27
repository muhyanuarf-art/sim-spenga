<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Keterangan lembar Analisis Hasil Tes Sumatif Lingkup Materi ke-n untuk
 * satu kelas × mapel × periode.
 *
 * Yang disimpan di sini HANYA hal yang tidak bisa disimpulkan dari nilai:
 * Materi Ajar (diketik guru), banyaknya butir soal, dan tanggal
 * pelaksanaan tes. Skor tiap butir soal 1–20 TIDAK disimpan — angka itu
 * diturunkan dari nilai sumatif di `nilai_siswas` lewat
 * App\Support\AnalisisButirSoal, supaya jumlahnya selalu sama persis
 * dengan nilai di Daftar Nilai walau nilainya dikoreksi kemudian.
 */
class AnalisisSumatif extends Model
{
    protected $table = 'analisis_sumatifs';

    public const DEFAULT_JUMLAH_SOAL = 20;

    protected $fillable = [
        'kelas_id', 'mata_pelajaran_id', 'tahun_ajaran_id', 'lingkup_materi',
        'materi_ajar', 'jumlah_soal', 'tanggal_pelaksanaan', 'diperbarui_oleh_id',
    ];

    protected function casts(): array
    {
        return [
            'lingkup_materi' => 'integer',
            'jumlah_soal' => 'integer',
            'tanggal_pelaksanaan' => 'date',
        ];
    }

    public function kelas(): BelongsTo { return $this->belongsTo(Kelas::class, 'kelas_id'); }
    public function mapel(): BelongsTo { return $this->belongsTo(MataPelajaran::class, 'mata_pelajaran_id'); }
    public function tahunAjaran(): BelongsTo { return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id'); }
    public function diperbaruiOleh(): BelongsTo { return $this->belongsTo(User::class, 'diperbarui_oleh_id'); }

    /**
     * Benih penciri lembar ini — dipakai AnalisisButirSoal untuk menentukan
     * tingkat kesukaran tiap butir soal. Harus SAMA untuk seluruh siswa di
     * lembar yang sama (supaya soal sukar sukar bagi sekelas), dan BERBEDA
     * antar lembar (supaya pola soal LM 1 tidak sama persis dengan LM 2).
     */
    public function benihKelas(): string
    {
        return "analisis|{$this->kelas_id}|{$this->mata_pelajaran_id}|{$this->tahun_ajaran_id}|{$this->lingkup_materi}";
    }
}
