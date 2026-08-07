<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JurnalMengajar extends Model
{
    use HasFactory;

    protected $fillable = [
        'jadwal_pelajaran_id', 'guru_id', 'kelas_id', 'mata_pelajaran_id', 'jam_pelajaran_id',
        'tanggal', 'materi', 'kegiatan', 'jumlah_hadir', 'jumlah_sakit', 'jumlah_izin', 'jumlah_alfa', 'keterangan',
    ];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function jadwal(): BelongsTo { return $this->belongsTo(JadwalPelajaran::class, 'jadwal_pelajaran_id'); }
    public function guru(): BelongsTo { return $this->belongsTo(User::class, 'guru_id'); }
    public function kelas(): BelongsTo { return $this->belongsTo(Kelas::class, 'kelas_id'); }
    public function mapel(): BelongsTo { return $this->belongsTo(MataPelajaran::class, 'mata_pelajaran_id'); }
    public function jamPelajaran(): BelongsTo { return $this->belongsTo(JamPelajaran::class, 'jam_pelajaran_id'); }
    public function absensi(): HasMany { return $this->hasMany(AbsensiSiswa::class, 'jurnal_mengajar_id'); }
}
