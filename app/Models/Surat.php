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

    // `nomor_kunci` SENGAJA tidak ikut $fillable — nilainya tidak pernah
    // boleh datang dari form, selalu dihitung sendiri di booted() di bawah.

    /**
     * `nomor_kunci` selalu diturunkan dari `nomor_surat` tepat sebelum
     * disimpan, jadi mustahil lepas sinkron — termasuk kalau surat dibuat
     * dari import, seeder, atau tinker, bukan cuma lewat SuratController.
     * Kolom inilah yang diberi unique index sebagai penjaga terakhir nomor
     * surat kembar (lihat migrasi 2026_08_28_000001 & NomorSuratBk::kunci()).
     */
    protected static function booted(): void
    {
        static::saving(function (self $surat) {
            $surat->nomor_kunci = \App\Support\NomorSuratBk::kunci($surat->nomor_surat);
        });
    }

    /**
     * Apakah nomor surat ini sudah dipakai surat LAIN? Dipakai validasi
     * SuratController saat menyimpan & mengubah surat. $kecualikanId diisi
     * saat mengubah, supaya surat tidak dianggap bentrok dengan dirinya
     * sendiri.
     */
    public static function nomorSudahDipakai(?string $nomorSurat, ?int $kecualikanId = null): ?self
    {
        $kunci = \App\Support\NomorSuratBk::kunci($nomorSurat);

        if ($kunci === null) {
            return null;
        }

        return static::with('siswa')
            ->where('nomor_kunci', $kunci)
            ->when($kecualikanId, fn ($q) => $q->whereKeyNot($kecualikanId))
            ->first();
    }

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
