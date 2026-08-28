<?php

namespace App\Models;

use App\Models\Concerns\MilikTahunAjaran;
use Illuminate\Database\Eloquent\Model;

class JenisPelanggaran extends Model
{
    // Master data per tahun ajaran — lihat trait & migrasi 2026_08_28_000003.
    use MilikTahunAjaran;

    protected $fillable = ['tahun_ajaran_id', 'kode', 'nama', 'kategori', 'poin_default', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
