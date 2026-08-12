<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisPelanggaran extends Model
{
    protected $fillable = ['kode', 'nama', 'kategori', 'poin_default', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
