<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TahunAjaran extends Model
{
    use HasFactory;

    protected $fillable = ['nama', 'semester', 'is_active', 'terkunci', 'terkunci_at', 'terkunci_oleh_id'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'terkunci' => 'boolean',
            'terkunci_at' => 'datetime',
        ];
    }

    public function terkunciOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'terkunci_oleh_id');
    }

    public static function aktif(): ?self
    {
        return static::where('is_active', true)->first();
    }

    /**
     * Tahap 2 (kunci periode). Dipakai App\Http\Middleware\EnsurePeriodeTidakTerkunci
     * untuk memblokir aksi tulis (jurnal, absensi, modul BK) HANYA kalau
     * periode yang terkunci adalah periode yang sedang AKTIF.
     */
    public function isTerkunci(): bool
    {
        return (bool) $this->terkunci;
    }

    /**
     * Tahap 3. Label gabungan untuk ditampilkan di UI, contoh:
     * "Tahun Ajaran 2026/2027 — Semester Ganjil".
     *
     * Ini HANYA perubahan tampilan (label), bukan perubahan skema.
     * Struktur tabel tahun_ajarans dipertahankan apa adanya (1 baris = 1
     * kombinasi tahun ajaran + semester) sesuai alternatif yang lebih
     * murah/rendah risiko dibanding memecah jadi tabel TahunAjaran + Semester
     * terpisah.
     */
    public function labelPeriode(): string
    {
        return "Tahun Ajaran {$this->nama} — Semester {$this->semester}";
    }

    /**
     * Versi singkat untuk tempat yang ruangnya terbatas (mis. badge header),
     * contoh: "2026/2027 · Semester Ganjil".
     */
    public function labelSingkat(): string
    {
        return "{$this->nama} · Semester {$this->semester}";
    }
}
