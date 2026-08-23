<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatKelasSiswa extends Model
{
    use HasFactory;

    public const JENIS_AWAL_MASUK = 'awal_masuk';
    public const JENIS_KENAIKAN_KELAS = 'kenaikan_kelas';
    public const JENIS_PINDAH_KELAS = 'pindah_kelas';

    protected $fillable = [
        'siswa_id', 'tahun_ajaran_id', 'kelas_asal_id', 'kelas_id', 'jenis',
        'tanggal_mutasi', 'keterangan', 'dicatat_oleh_id',
    ];

    protected function casts(): array
    {
        return ['tanggal_mutasi' => 'date'];
    }

    /** Label ramah-tampil untuk kolom `jenis`. */
    public function labelJenis(): string
    {
        return match ($this->jenis) {
            self::JENIS_AWAL_MASUK => 'Awal Masuk',
            self::JENIS_KENAIKAN_KELAS => 'Kenaikan Kelas',
            self::JENIS_PINDAH_KELAS => 'Pindah Kelas',
            default => '-',
        };
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }

    public function kelasAsal(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_asal_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function dicatatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh_id');
    }
}
