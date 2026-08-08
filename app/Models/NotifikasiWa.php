<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotifikasiWa extends Model
{
    use HasFactory;

    protected $table = 'notifikasi_was';

    /**
     * Maksimal percobaan kirim total (sesuai aturan: gagal 2x = berhenti,
     * kemungkinan nomor bukan WhatsApp).
     */
    public const MAKS_PERCOBAAN = 2;

    protected $fillable = [
        'siswa_id', 'kelas_id', 'tanggal', 'status', 'percobaan_ke',
        'wa_message_id', 'no_hp_tujuan', 'pesan', 'keterangan_gagal',
        'terkirim_at', 'diterima_at', 'dibaca_at', 'gagal_at',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'terkirim_at' => 'datetime',
            'diterima_at' => 'datetime',
            'dibaca_at' => 'datetime',
            'gagal_at' => 'datetime',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function bisaDicobaLagi(): bool
    {
        return $this->status === 'gagal' && $this->percobaan_ke < self::MAKS_PERCOBAAN;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'menunggu' => 'Menunggu',
            'terkirim' => 'Terkirim',
            'diterima' => 'Diterima',
            'dibaca' => 'Telah Dibaca',
            'gagal' => 'Gagal',
            default => ucfirst($this->status),
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'menunggu' => 'bg-slate-100 text-slate-600',
            'terkirim' => 'bg-blue-100 text-blue-700',
            'diterima' => 'bg-indigo-100 text-indigo-700',
            'dibaca' => 'bg-emerald-100 text-emerald-700',
            'gagal' => 'bg-red-100 text-red-700',
            default => 'bg-slate-100 text-slate-600',
        };
    }
}
