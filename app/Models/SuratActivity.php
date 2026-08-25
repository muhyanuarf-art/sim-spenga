<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class SuratActivity extends Model
{
    use HasFactory;

    protected $fillable = ['surat_id', 'user_id', 'aktivitas', 'keterangan', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function surat(): BelongsTo
    {
        return $this->belongsTo(Surat::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Catat 1 baris riwayat — dipanggil dari controller manapun yang mengubah surat. */
    public static function catat(Surat $surat, string $aktivitas, ?string $keterangan = null, ?array $metadata = null): self
    {
        return self::create([
            'surat_id' => $surat->id,
            'user_id' => Auth::id(),
            'aktivitas' => $aktivitas,
            'keterangan' => $keterangan,
            'metadata' => $metadata,
        ]);
    }
}
