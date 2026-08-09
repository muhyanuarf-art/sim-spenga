<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotifikasiAlfaTerkirim extends Model
{
    protected $table = 'notifikasi_alfa_terkirims';

    protected $fillable = ['siswa_id', 'tanggal', 'dikirim_at', 'status_kirim'];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'dikirim_at' => 'datetime'];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }
}
