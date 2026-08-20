<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenguranganPoinSiswa extends Model
{
    protected $table = 'pengurangan_poin_siswas';

    protected $fillable = [
        'siswa_id', 'tahun_ajaran_id', 'tanggal', 'jumlah', 'alasan',
        'dasar_rekomendasi', 'catatan', 'petugas_id',
        'dibatalkan_at', 'dibatalkan_oleh_id', 'alasan_pembatalan',
    ];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'dibatalkan_at' => 'datetime'];
    }

    public function siswa(): BelongsTo { return $this->belongsTo(Siswa::class, 'siswa_id'); }
    public function petugas(): BelongsTo { return $this->belongsTo(User::class, 'petugas_id'); }
    public function dibatalkanOleh(): BelongsTo { return $this->belongsTo(User::class, 'dibatalkan_oleh_id'); }
    public function tahunAjaran(): BelongsTo { return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id'); }

    public function scopeAktif($query)
    {
        return $query->whereNull('dibatalkan_at');
    }
}
