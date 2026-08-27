<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Header satu lembar Daftar Nilai = (kelas × mata pelajaran × periode).
 *
 * Dua kegunaannya:
 *
 * 1. STATUS FINALISASI. Selama masih 'draft', guru mapel bebas mengubah
 *    nilai. Setelah ditekan "Finalisasi", lembar itu terkunci — nilai yang
 *    sudah dipakai wali kelas untuk menyusun rapor tidak bisa berubah
 *    diam-diam. Kalau ternyata ada koreksi, Kurikulum/Admin yang membuka
 *    kuncinya (tercatat siapa & kapan, sama seperti buka kunci periode).
 *
 * 2. MONITORING. Kurikulum & Kepala Sekolah bisa melihat mapel mana di
 *    kelas mana yang nilainya belum diisi/belum difinalisasi, tanpa harus
 *    membuka lembarnya satu per satu.
 */
class PenilaianKelasMapel extends Model
{
    protected $table = 'penilaian_kelas_mapels';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_FINAL = 'final';

    protected $fillable = [
        'kelas_id', 'mata_pelajaran_id', 'tahun_ajaran_id', 'guru_id',
        'status', 'difinalisasi_at', 'difinalisasi_oleh_id', 'dibuka_at', 'dibuka_oleh_id',
    ];

    protected function casts(): array
    {
        return [
            'difinalisasi_at' => 'datetime',
            'dibuka_at' => 'datetime',
        ];
    }

    public function kelas(): BelongsTo { return $this->belongsTo(Kelas::class, 'kelas_id'); }
    public function mapel(): BelongsTo { return $this->belongsTo(MataPelajaran::class, 'mata_pelajaran_id'); }
    public function tahunAjaran(): BelongsTo { return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id'); }
    public function guru(): BelongsTo { return $this->belongsTo(User::class, 'guru_id'); }
    public function difinalisasiOleh(): BelongsTo { return $this->belongsTo(User::class, 'difinalisasi_oleh_id'); }
    public function dibukaOleh(): BelongsTo { return $this->belongsTo(User::class, 'dibuka_oleh_id'); }

    public function isFinal(): bool
    {
        return $this->status === self::STATUS_FINAL;
    }

    public function statusLabel(): string
    {
        return $this->isFinal() ? 'Final' : 'Draft';
    }

    public function statusBadgeClass(): string
    {
        return $this->isFinal()
            ? 'bg-emerald-50 text-emerald-700'
            : 'bg-amber-50 text-amber-700';
    }
}
