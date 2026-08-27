<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotifikasiAlfaTerkirim extends Model
{
    protected $table = 'notifikasi_alfa_terkirims';

    /**
     * Maksimal total percobaan kirim kalau gagal karena alasan yang
     * kemungkinan besar terkait NOMOR (bukan gangguan teknis sesaat).
     * Sesuai aturan sekolah: gagal 1x -> coba lagi 1x -> kalau masih
     * gagal juga (2x total), berhenti, kemungkinan nomor bukan WhatsApp.
     */
    public const MAKS_PERCOBAAN = 2;

    protected $fillable = [
        'siswa_id', 'tanggal', 'mata_pelajaran_id', 'kegiatan_sekolah_id', 'jam_ke',
        'dikirim_at', 'status_kirim', 'percobaan_ke', 'keterangan_gagal',
    ];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'dikirim_at' => 'datetime'];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function mapel(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'mata_pelajaran_id');
    }

    /** Terisi kalau Alfa-nya terjadi pada kegiatan sekolah di luar jam KBM. */
    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(KegiatanSekolah::class, 'kegiatan_sekolah_id');
    }

    /** Label konteks untuk ditampilkan di histori: mapel atau nama kegiatan. */
    public function konteksLabel(): string
    {
        if ($this->kegiatan_sekolah_id) {
            return $this->kegiatan?->nama ?? 'Kegiatan sekolah';
        }

        return $this->mapel?->nama_mapel ?? '-';
    }

    /**
     * Boleh dicoba kirim ulang lagi hanya kalau statusnya gagal DAN
     * belum mencapai batas MAKS_PERCOBAAN.
     */
    public function bisaDicobaLagi(): bool
    {
        return $this->status_kirim === 'gagal' && $this->percobaan_ke < self::MAKS_PERCOBAAN;
    }

    public function statusLabel(): string
    {
        return match ($this->status_kirim) {
            'pending' => 'Menunggu',
            'terkirim' => 'Terkirim',
            'gagal' => 'Gagal',
            'dilewati' => 'Dilewati (Isi Terlambat)',
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
            default => 'bg-slate-100 text-slate-600',
        };
    }
}
