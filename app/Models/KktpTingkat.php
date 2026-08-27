<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * KKTP (Kriteria Ketercapaian Tujuan Pembelajaran) per TINGKAT kelas,
 * per periode. Sesuai ketentuan sekolah: Kelas 7, 8, dan 9 masing-masing
 * 73 – 82.
 *
 * - kktp_min (73) = ambang TUNTAS. Nilai sumatif di bawah angka ini wajib
 *   diikuti remedi (kolom REM di daftar nilai).
 * - kktp_max (82) = batas atas rentang "tercapai pada tingkat minimum",
 *   dipakai untuk menentukan predikat (lihat App\Support\SkemaPenilaian).
 */
class KktpTingkat extends Model
{
    protected $table = 'kktp_tingkats';

    /** Tingkat yang ada di sekolah ini (SMP). */
    public const TINGKAT = [7, 8, 9];

    public const DEFAULT_MIN = 73;
    public const DEFAULT_MAX = 82;

    protected $fillable = ['tahun_ajaran_id', 'tingkat', 'kktp_min', 'kktp_max'];

    protected function casts(): array
    {
        return [
            'tingkat' => 'integer',
            'kktp_min' => 'integer',
            'kktp_max' => 'integer',
        ];
    }

    /** Cache per (periode, tingkat) dalam 1 request. */
    protected static array $cached = [];

    /** KKTP satu tingkat; dibuatkan otomatis dengan default 73–82 kalau belum ada. */
    public static function untuk(TahunAjaran $periode, int $tingkat): self
    {
        $kunci = $periode->id.'|'.$tingkat;

        return static::$cached[$kunci] ??= static::firstOrCreate(
            ['tahun_ajaran_id' => $periode->id, 'tingkat' => $tingkat],
            ['kktp_min' => self::DEFAULT_MIN, 'kktp_max' => self::DEFAULT_MAX]
        );
    }

    /** Semua tingkat sekaligus, untuk halaman pengaturan Kurikulum. */
    public static function semuaUntuk(TahunAjaran $periode): \Illuminate\Support\Collection
    {
        return collect(self::TINGKAT)->mapWithKeys(
            fn (int $tingkat) => [$tingkat => static::untuk($periode, $tingkat)]
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
}
