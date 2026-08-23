<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ekstrakurikuler extends Model
{
    use HasFactory;

    protected $table = 'ekstrakurikulers';

    protected $fillable = ['nama_ekstrakurikuler', 'keterangan', 'is_aktif'];

    protected function casts(): array
    {
        return ['is_aktif' => 'boolean'];
    }

    /**
     * (2026-08-23, revisi) — bisa punya BANYAK pembina, campuran staf
     * sekolah (user_id terisi) maupun dari luar sekolah (nama_eksternal
     * terisi, tidak punya akun). Lihat App\Models\EkstrakurikulerPembina.
     */
    public function pembinas(): HasMany
    {
        return $this->hasMany(EkstrakurikulerPembina::class);
    }

    /** Daftar nama pembina (internal + eksternal) digabung, untuk tampilan ringkas. */
    public function daftarNamaPembina(): string
    {
        $nama = $this->pembinas->map(fn ($p) => $p->namaTampil() . ($p->isEksternal() ? ' (luar sekolah)' : ''));
        return $nama->isEmpty() ? '—' : $nama->implode(', ');
    }
}
