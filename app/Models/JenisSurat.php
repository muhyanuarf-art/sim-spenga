<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisSurat extends Model
{
    use HasFactory;

    protected $fillable = ['nama_jenis', 'template_isi'];

    public function surats(): HasMany
    {
        return $this->hasMany(Surat::class);
    }
}
