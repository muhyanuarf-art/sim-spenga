<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengaturanSekolah extends Model
{
    protected $table = 'pengaturan_sekolahs';

    protected $fillable = [
        'nama_sekolah',
        'kabupaten_kota',
        'provinsi',
        'nama_kepala_sekolah',
        'nip_kepala_sekolah',
        'format_lokasi_ttd',
    ];

    /** Cache di dalam 1 request, supaya tidak query berkali-kali (dipakai lewat View composer global). */
    protected static ?self $cached = null;

    /**
     * Baris pengaturan tunggal (singleton). Dibuat otomatis dengan nilai
     * default kalau memang belum pernah ada (mis. sebelum migration baru
     * dijalankan ulang / di server lain).
     */
    public static function current(): self
    {
        return static::$cached ??= static::first() ?? static::create([
            'kabupaten_kota' => 'Bumiayu',
            'provinsi' => 'Jawa Tengah',
        ]);
    }

    /** Reset cache — dipanggil setelah update supaya perubahan langsung kepakai di request yang sama. */
    public static function lupakanCache(): void
    {
        static::$cached = null;
    }

    /** Teks lokasi yang dipakai di baris tanda tangan (override kalau diisi, fallback ke Kabupaten/Kota). */
    public function lokasiTtd(): string
    {
        return $this->format_lokasi_ttd ?: ($this->kabupaten_kota ?: '-');
    }
}
