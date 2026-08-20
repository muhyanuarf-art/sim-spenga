<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PembinaanSiswa extends Model
{
    protected $fillable = [
        'siswa_id', 'kasus_siswa_id', 'tahun_ajaran_id', 'tanggal', 'tahap',
        'jenis_pembinaan', 'catatan_bk', 'hasil_pembinaan', 'bukti_file', 'status',
        'tanggal_evaluasi_berikutnya', 'ruang_refleksi_selesai', 'petugas_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'tanggal_evaluasi_berikutnya' => 'date',
            'ruang_refleksi_selesai' => 'date',
        ];
    }

    public function getBuktiFileUrlAttribute(): ?string
    {
        return $this->bukti_file ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->bukti_file) : null;
    }

    public function siswa(): BelongsTo { return $this->belongsTo(Siswa::class, 'siswa_id'); }
    public function kasus(): BelongsTo { return $this->belongsTo(KasusSiswa::class, 'kasus_siswa_id'); }
    public function petugas(): BelongsTo { return $this->belongsTo(User::class, 'petugas_id'); }
    public function tahunAjaran(): BelongsTo { return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id'); }
    public function evaluasiHarian(): HasMany { return $this->hasMany(EvaluasiPembinaan::class, 'pembinaan_siswa_id')->orderBy('hari_ke'); }
}
