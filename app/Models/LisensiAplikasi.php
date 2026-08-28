<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris catatan aktivasi lisensi aplikasi.
 * Seluruh aturannya ada di App\Support\Lisensi — model ini hanya wadahnya.
 */
class LisensiAplikasi extends Model
{
    protected $table = 'lisensi_aplikasis';

    protected $fillable = [
        'kunci_hash', 'host', 'tanda_tangan', 'diaktifkan_at', 'diaktifkan_oleh',
    ];

    protected function casts(): array
    {
        return ['diaktifkan_at' => 'datetime'];
    }
}
