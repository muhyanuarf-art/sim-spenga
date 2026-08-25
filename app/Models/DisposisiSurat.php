<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisposisiSurat extends Model
{
    use HasFactory;

    protected $fillable = [
        'surat_id', 'dari_user_id', 'kepada_user_id', 'instruksi',
        'batas_waktu', 'status', 'catatan_penyelesaian', 'dibaca_at', 'selesai_at',
    ];

    protected function casts(): array
    {
        return ['batas_waktu' => 'date', 'dibaca_at' => 'datetime', 'selesai_at' => 'datetime'];
    }

    public function surat(): BelongsTo
    {
        return $this->belongsTo(Surat::class);
    }

    public function dariUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dari_user_id');
    }

    public function kepadaUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kepada_user_id');
    }
}
