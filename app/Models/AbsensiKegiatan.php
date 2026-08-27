<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * "Kepala" absensi satu kelas pada satu kegiatan di satu tanggal —
 * setara JurnalMengajar untuk KBM. Rincian per siswa ada di absensi_siswas.
 */
class AbsensiKegiatan extends Model
{
    protected $table = 'absensi_kegiatans';

    protected $fillable = [
        'kegiatan_sekolah_id', 'kelas_id', 'tanggal', 'diisi_oleh_id', 'catatan',
        'jumlah_hadir', 'jumlah_sakit', 'jumlah_izin', 'jumlah_alfa',
    ];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function kegiatan(): BelongsTo { return $this->belongsTo(KegiatanSekolah::class, 'kegiatan_sekolah_id'); }
    public function kelas(): BelongsTo { return $this->belongsTo(Kelas::class, 'kelas_id'); }
    public function diisiOleh(): BelongsTo { return $this->belongsTo(User::class, 'diisi_oleh_id'); }
    public function absensi(): HasMany { return $this->hasMany(AbsensiSiswa::class, 'absensi_kegiatan_id'); }
}
