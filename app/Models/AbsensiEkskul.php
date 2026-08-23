<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AbsensiEkskul extends Model
{
    use HasFactory;

    protected $table = 'absensi_ekskuls';

    protected $fillable = ['ekstrakurikuler_id', 'tanggal', 'dicatat_oleh_id', 'kegiatan', 'keterangan'];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function ekstrakurikuler(): BelongsTo
    {
        return $this->belongsTo(Ekstrakurikuler::class);
    }

    public function dicatatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh_id');
    }

    public function peserta(): HasMany
    {
        return $this->hasMany(AbsensiEkskulPeserta::class);
    }
}
