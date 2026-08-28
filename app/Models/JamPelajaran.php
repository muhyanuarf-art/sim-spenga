<?php

namespace App\Models;

use App\Models\Concerns\MilikTahunAjaran;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JamPelajaran extends Model
{
    use HasFactory;
    // Master data per tahun ajaran — lihat trait & migrasi 2026_08_28_000003.
    use MilikTahunAjaran;

    protected $fillable = ['tahun_ajaran_id', 'hari', 'jam_ke', 'jam_mulai', 'jam_selesai', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public static function HARI_LIST(): array
    {
        return ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    }

    public function getLabelAttribute(): string
    {
        $mulai = substr($this->jam_mulai, 0, 5);
        $selesai = substr($this->jam_selesai, 0, 5);
        return "Jam ke-{$this->jam_ke} ({$mulai} - {$selesai})";
    }
}
