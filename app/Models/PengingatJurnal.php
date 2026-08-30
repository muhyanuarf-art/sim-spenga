<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris = satu sesi mengajar pada satu tanggal yang sudah diproses
 * oleh pengingat. Lihat migrasi 2026_08_29_000004 untuk alasan bentuknya.
 */
class PengingatJurnal extends Model
{
    protected $table = 'pengingat_jurnals';

    /**
     * Batas percobaan kirim saat Fonnte menyatakan masalahnya ada pada
     * NOMOR guru. Disamakan dengan notifikasi Alfa (lihat
     * NotifikasiAlfaTerkirim::MAKS_PERCOBAAN) supaya perilakunya seragam.
     */
    public const MAKS_PERCOBAAN = 2;

    protected $fillable = [
        'guru_id', 'jadwal_pelajaran_id', 'tahun_ajaran_id', 'kelas_id',
        'mata_pelajaran_id', 'tanggal', 'jam_ke_awal', 'jam_ke_akhir',
        'status_kirim', 'percobaan_ke', 'keterangan_gagal', 'dikirim_at',
    ];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'dikirim_at' => 'datetime'];
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function mapel(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'mata_pelajaran_id');
    }

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(JadwalPelajaran::class, 'jadwal_pelajaran_id');
    }

    /** Label jam, mis. "3" atau "3-4". */
    public function labelJam(): string
    {
        return $this->jam_ke_awal === $this->jam_ke_akhir
            ? (string) $this->jam_ke_awal
            : "{$this->jam_ke_awal}-{$this->jam_ke_akhir}";
    }

    public function statusLabel(): string
    {
        return match ($this->status_kirim) {
            'pending' => 'Menunggu',
            'terkirim' => 'Terkirim',
            'gagal' => 'Gagal',
            'dilewati' => 'Dilewati (Keburu Diisi)',
            'kedaluwarsa' => 'Kedaluwarsa (Harinya Lewat)',
            default => ucfirst($this->status_kirim),
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status_kirim) {
            'pending' => 'bg-slate-100 text-slate-600',
            'terkirim' => 'bg-emerald-100 text-emerald-700',
            'gagal' => 'bg-red-100 text-red-700',
            'dilewati' => 'bg-sky-100 text-sky-700',
            // Sengaja kuning, bukan biru seperti 'dilewati': ini keadaan yang
            // perlu diperiksa Admin, bukan kabar baik.
            'kedaluwarsa' => 'bg-amber-100 text-amber-700',
            default => 'bg-slate-100 text-slate-600',
        };
    }
}
