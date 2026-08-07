<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuruMengajarKelas extends Model
{
    use HasFactory;

    protected $table = 'guru_mengajar_kelas';

    protected $fillable = ['guru_id', 'kelas_id', 'mata_pelajaran_id', 'tahun_ajaran_id'];

    public function guru(): BelongsTo { return $this->belongsTo(User::class, 'guru_id'); }
    public function kelas(): BelongsTo { return $this->belongsTo(Kelas::class, 'kelas_id'); }
    public function mapel(): BelongsTo { return $this->belongsTo(MataPelajaran::class, 'mata_pelajaran_id'); }
    public function tahunAjaran(): BelongsTo { return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id'); }
}
