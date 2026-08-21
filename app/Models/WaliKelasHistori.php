<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * STEP 4 — Histori wali kelas PER TAHUN AJARAN (bukan per semester).
 * Lihat catatan desain lengkap di migrasi
 * 2026_08_20_000004_create_wali_kelas_histori_table.php.
 */
class WaliKelasHistori extends Model
{
    protected $table = 'wali_kelas_histori';

    protected $fillable = ['kelas_id', 'tahun_ajaran_nama', 'wali_kelas_id', 'diatur_oleh_id'];

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'wali_kelas_id');
    }

    public function diaturOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diatur_oleh_id');
    }
}
