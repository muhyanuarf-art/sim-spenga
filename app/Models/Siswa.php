<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Siswa extends Model
{
    use HasFactory;

    protected $table = 'siswas';

    protected $fillable = ['nis', 'nisn', 'nama', 'nama_ortu', 'no_wa_ortu', 'jenis_kelamin', 'kelas_id', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(AbsensiSiswa::class, 'siswa_id');
    }

    public function orangTua(): HasOne
    {
        return $this->hasOne(OrangTua::class, 'siswa_id');
    }

    // ==== Modul BK ====
    public function kasusBk(): HasMany { return $this->hasMany(KasusSiswa::class, 'siswa_id'); }
    public function pembinaanBk(): HasMany { return $this->hasMany(PembinaanSiswa::class, 'siswa_id'); }
    public function penguranganPoinBk(): HasMany { return $this->hasMany(PenguranganPoinSiswa::class, 'siswa_id'); }
    public function pemanggilanOrtuBk(): HasMany { return $this->hasMany(PemanggilanOrangTua::class, 'siswa_id'); }
}
