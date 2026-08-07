<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsensiSiswa extends Model
{
    use HasFactory;

    protected $table = 'absensi_siswas';

    protected $fillable = [
        'jurnal_mengajar_id', 'siswa_id', 'kelas_id', 'tanggal', 'status', 'keterangan',
    ];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function jurnal(): BelongsTo { return $this->belongsTo(JurnalMengajar::class, 'jurnal_mengajar_id'); }
    public function siswa(): BelongsTo { return $this->belongsTo(Siswa::class, 'siswa_id'); }
    public function kelas(): BelongsTo { return $this->belongsTo(Kelas::class, 'kelas_id'); }
}
