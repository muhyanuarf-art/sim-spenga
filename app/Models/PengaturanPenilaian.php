<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bobot & bentuk daftar nilai yang berlaku pada SATU periode (tahun ajaran
 * + semester). Diatur oleh Kurikulum.
 *
 * Sengaja per periode, bukan satu baris global: kalau Kurikulum mengubah
 * bobot untuk semester depan, nilai semester yang sudah lewat tidak ikut
 * berubah angkanya — rapor yang sudah dibagikan tetap bisa
 * dipertanggungjawabkan.
 */
class PengaturanPenilaian extends Model
{
    protected $table = 'pengaturan_penilaians';

    protected $fillable = [
        'tahun_ajaran_id',
        'bobot_formatif_sumatif', 'bobot_asts', 'bobot_asas',
        'komposisi_formatif', 'komposisi_sumatif_lm',
        'jumlah_tpf', 'jumlah_lm',
        'kebijakan_remedial',
        'diperbarui_oleh_id',
    ];

    protected function casts(): array
    {
        return [
            'bobot_formatif_sumatif' => 'integer',
            'bobot_asts' => 'integer',
            'bobot_asas' => 'integer',
            'komposisi_formatif' => 'integer',
            'komposisi_sumatif_lm' => 'integer',
            'jumlah_tpf' => 'integer',
            'jumlah_lm' => 'integer',
        ];
    }

    /** Nilai default kalau periode ini belum pernah diatur Kurikulum. */
    public const DEFAULT = [
        'bobot_formatif_sumatif' => 60,
        'bobot_asts' => 20,
        'bobot_asas' => 20,
        'komposisi_formatif' => 50,
        'komposisi_sumatif_lm' => 50,
        'jumlah_tpf' => 7,
        'jumlah_lm' => 4,
        'kebijakan_remedial' => 'batas_kktp',
    ];

    /** Cache per periode dalam 1 request (pola yang sama dengan PengaturanSekolah::current()). */
    protected static array $cached = [];

    /**
     * Pengaturan untuk satu periode; dibuatkan otomatis dengan nilai
     * default kalau Kurikulum belum pernah mengaturnya — supaya guru bisa
     * langsung mengisi nilai di hari pertama tanpa harus menunggu
     * Kurikulum membuka menu pengaturan lebih dulu.
     */
    public static function untukPeriode(TahunAjaran $periode): self
    {
        return static::$cached[$periode->id] ??= static::firstOrCreate(
            ['tahun_ajaran_id' => $periode->id],
            self::DEFAULT
        );
    }

    public static function lupakanCache(): void
    {
        static::$cached = [];
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }

    public function diperbaruiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diperbarui_oleh_id');
    }

    /** Total ketiga bobot utama — harus 100 (divalidasi di controller). */
    public function totalBobot(): int
    {
        return $this->bobot_formatif_sumatif + $this->bobot_asts + $this->bobot_asas;
    }
}
