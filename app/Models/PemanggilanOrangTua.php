<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PemanggilanOrangTua extends Model
{
    protected $table = 'pemanggilan_orangtuas';

    public const STATUS_MENUNGGU_PERTEMUAN = 'menunggu_pertemuan';
    public const STATUS_SELESAI = 'selesai';

    protected $fillable = [
        'siswa_id', 'kasus_siswa_id', 'surat_id', 'tahun_ajaran_id', 'tanggal', 'alasan',
        'status', 'ortu_hadir', 'hasil_pertemuan', 'bukti_file', 'kesepakatan', 'petugas_id',
    ];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'ortu_hadir' => 'boolean'];
    }

    public function sudahAdaHasil(): bool
    {
        return $this->status === self::STATUS_SELESAI;
    }

    public function getBuktiFileUrlAttribute(): ?string
    {
        // Lewat rute berautentikasi, bukan tautan publik — lihat
        // App\Http\Controllers\BerkasTerlindungiController.
        return \App\Http\Controllers\BerkasTerlindungiController::url($this->bukti_file);
    }

    public function siswa(): BelongsTo { return $this->belongsTo(Siswa::class, 'siswa_id'); }
    public function kasus(): BelongsTo { return $this->belongsTo(KasusSiswa::class, 'kasus_siswa_id'); }
    public function petugas(): BelongsTo { return $this->belongsTo(User::class, 'petugas_id'); }

    /** Surat Panggilan Orang Tua resmi (dari Manajemen Surat) yang menyertai pemanggilan ini. */
    public function surat(): BelongsTo { return $this->belongsTo(Surat::class); }
}
