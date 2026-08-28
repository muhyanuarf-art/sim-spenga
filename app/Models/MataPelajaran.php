<?php

namespace App\Models;

use App\Models\Concerns\MilikTahunAjaran;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MataPelajaran extends Model
{
    use HasFactory;
    // Master data per tahun ajaran — lihat trait & migrasi 2026_08_28_000003.
    use MilikTahunAjaran;

    protected $table = 'mata_pelajarans';

    protected $fillable = ['tahun_ajaran_id', 'kode', 'nama_mapel'];
}
