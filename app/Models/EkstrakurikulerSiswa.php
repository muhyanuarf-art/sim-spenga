<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EkstrakurikulerSiswa extends Model
{
    use HasFactory;

    protected $table = 'ekstrakurikuler_siswas';

    protected $fillable = ['ekstrakurikuler_id', 'siswa_id', 'tanggal_gabung'];

    protected function casts(): array
    {
        return ['tanggal_gabung' => 'date'];
    }

    public function ekstrakurikuler(): BelongsTo
    {
        return $this->belongsTo(Ekstrakurikuler::class);
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }
}
