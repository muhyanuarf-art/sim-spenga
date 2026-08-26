<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Surat extends Model
{
    use HasFactory;

    protected $fillable = [
        'jenis_surat_id', 'siswa_id', 'tahun_ajaran_id', 'arah', 'status',
        'sifat', 'asal_surat', 'tujuan_surat', 'nomor_surat', 'nomor_urut',
        'tanggal', 'tanggal_diterima', 'tanggal_acara', 'waktu_acara',
        'isi', 'data_formulir', 'keterangan', 'dibuat_oleh_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date', 'tanggal_diterima' => 'date', 'tanggal_acara' => 'date',
            'data_formulir' => 'array',
        ];
    }

    public function jenisSurat(): BelongsTo
    {
        return $this->belongsTo(JenisSurat::class);
    }

    /** Siswa utama (backward-compat kolom siswa_id, tampilan ringkas). */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    /** Semua siswa terkait surat ini — lihat surat_siswa (tahap 1). */
    public function siswas(): BelongsToMany
    {
        return $this->belongsToMany(Siswa::class, 'surat_siswa');
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh_id');
    }

    public function disposisi(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DisposisiSurat::class)->orderByDesc('created_at');
    }

    /**
     * Disposisi PALING BARU saja, 1 baris per surat — dipakai di
     * Dashboard Surat (tabel "Surat Masuk Terbaru"). BUKAN pakai
     * `disposisi()->limit(1)` di eager-load closure — itu cuma
     * membatasi jumlah baris TOTAL gabungan semua surat, bukan per
     * surat (jebakan umum Eloquent). `latestOfMany()` yang benar.
     */
    public function disposisiTerbaru(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(DisposisiSurat::class)->latestOfMany();
    }

    public function attachments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SuratAttachment::class);
    }

    public function activities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SuratActivity::class)->orderByDesc('created_at');
    }
}
