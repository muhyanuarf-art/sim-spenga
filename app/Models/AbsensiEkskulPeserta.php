<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 1 baris kehadiran untuk 1 sesi. Hanya salah satu dari `siswa_id` /
 * `ekstrakurikuler_pembina_id` yang terisi — lihat penjelasan lengkap di
 * migrasi `2026_08_23_000005_create_absensi_ekskuls_table`.
 */
class AbsensiEkskulPeserta extends Model
{
    use HasFactory;

    protected $table = 'absensi_ekskul_pesertas';

    protected $fillable = ['absensi_ekskul_id', 'siswa_id', 'ekstrakurikuler_pembina_id', 'status'];

    public function absensiEkskul(): BelongsTo
    {
        return $this->belongsTo(AbsensiEkskul::class);
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function pembina(): BelongsTo
    {
        return $this->belongsTo(EkstrakurikulerPembina::class, 'ekstrakurikuler_pembina_id');
    }

    public function isPembina(): bool
    {
        return $this->ekstrakurikuler_pembina_id !== null;
    }
}
