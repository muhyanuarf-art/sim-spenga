<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\MilikTahunAjaran;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisSurat extends Model
{
    use HasFactory;
    // Master data per tahun ajaran — lihat trait & migrasi 2026_08_28_000003.
    use MilikTahunAjaran;

    protected $fillable = ['tahun_ajaran_id', 'nama_jenis', 'kode_jenis', 'kategori', 'tipe_formulir', 'template_isi', 'is_aktif'];

    public const TIPE_BEBAS = 'bebas';
    public const TIPE_IZIN_MENINGGALKAN_PELAJARAN = 'izin_meninggalkan_pelajaran';
    public const TIPE_KETERANGAN_TERLAMBAT = 'keterangan_terlambat';
    public const TIPE_PERNYATAAN_PELANGGARAN = 'pernyataan_pelanggaran';

    protected function casts(): array
    {
        return ['is_aktif' => 'boolean'];
    }

    public function surats(): HasMany
    {
        return $this->hasMany(Surat::class);
    }
}
