<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TahunAjaran extends Model
{
    use HasFactory;

    /**
     * STEP 1 — status adalah label siklus hidup periode untuk TAMPILAN
     * admin saja (Bagian 3 & 4): akan_datang | aktif | selesai.
     * Ini TERPISAH dari `terkunci` (lock, lingkup STEP 2) dan tidak
     * menggantikan `is_active` sebagai sumber kebenaran periode aktif —
     * lihat static::aktif() & App\Support\PeriodeAkademik.
     */
    public const STATUS_AKAN_DATANG = 'akan_datang';
    public const STATUS_AKTIF = 'aktif';
    public const STATUS_SELESAI = 'selesai';

    protected $fillable = [
        'nama', 'semester', 'tanggal_mulai', 'tanggal_selesai', 'status',
        'is_active', 'terkunci', 'terkunci_at', 'terkunci_oleh_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
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

    /**
     * Label status siklus hidup untuk ditampilkan di admin (Bagian 3, 4, 7).
     * is_active tetap dihormati sebagai sumber kebenaran: kalau baris ini
     * sedang aktif, badge SELALU menunjukkan "Aktif" walau kolom `status`
     * tersimpan belum sempat disinkronkan.
     */
    public function statusLabel(): string
    {
        if ($this->is_active) {
            return 'Aktif';
        }

        return match ($this->status) {
            self::STATUS_SELESAI => 'Selesai',
            self::STATUS_AKTIF => 'Akan Datang', // aktif tersimpan tapi is_active sudah false → sudah tidak berlaku
            default => 'Akan Datang',
        };
    }

    /** Kelas badge Tailwind untuk statusLabel(), dipakai di view. */
    public function statusBadgeClass(): string
    {
        if ($this->is_active) {
            return 'bg-emerald-50 text-emerald-700';
        }

        return $this->status === self::STATUS_SELESAI
            ? 'bg-slate-100 text-slate-500'
            : 'bg-amber-50 text-amber-700';
    }

    /** Rentang tanggal untuk ditampilkan, mis. "13 Jul 2026 – 20 Jun 2027". */
    public function rentangTanggal(): ?string
    {
        if (! $this->tanggal_mulai || ! $this->tanggal_selesai) {
            return null;
        }

        return $this->tanggal_mulai->translatedFormat('d M Y').' – '.$this->tanggal_selesai->translatedFormat('d M Y');
    }
}
