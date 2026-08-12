<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluasiPembinaan extends Model
{
    protected $fillable = ['pembinaan_siswa_id', 'hari_ke', 'tanggal', 'kondisi', 'catatan', 'petugas_id'];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function pembinaan(): BelongsTo { return $this->belongsTo(PembinaanSiswa::class, 'pembinaan_siswa_id'); }
    public function petugas(): BelongsTo { return $this->belongsTo(User::class, 'petugas_id'); }
}
