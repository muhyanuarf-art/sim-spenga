<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PemanggilanOrangTua extends Model
{
    protected $table = 'pemanggilan_orangtuas';

    protected $fillable = [
        'siswa_id', 'kasus_siswa_id', 'tahun_ajaran_id', 'tanggal', 'alasan',
        'ortu_hadir', 'hasil_pertemuan', 'bukti_file', 'kesepakatan', 'petugas_id',
    ];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'ortu_hadir' => 'boolean'];
    }

    public function getBuktiFileUrlAttribute(): ?string
    {
        return $this->bukti_file ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->bukti_file) : null;
    }

    public function siswa(): BelongsTo { return $this->belongsTo(Siswa::class, 'siswa_id'); }
    public function kasus(): BelongsTo { return $this->belongsTo(KasusSiswa::class, 'kasus_siswa_id'); }
    public function petugas(): BelongsTo { return $this->belongsTo(User::class, 'petugas_id'); }
}
