<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KasusSiswa extends Model
{
    protected $fillable = [
        'siswa_id', 'kelas_id', 'jenis_pelanggaran_id', 'tahun_ajaran_id',
        'tanggal_kejadian', 'nama_pelanggaran', 'kategori', 'poin', 'kronologi',
        'guru_pelapor_id', 'bukti_catatan', 'bukti_file', 'status',
        'dibatalkan_at', 'dibatalkan_oleh_id', 'alasan_pembatalan',
    ];

    protected function casts(): array
    {
        return ['tanggal_kejadian' => 'date', 'dibatalkan_at' => 'datetime'];
    }

    public function getBuktiFileUrlAttribute(): ?string
    {
        return $this->bukti_file ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->bukti_file) : null;
    }

    public function siswa(): BelongsTo { return $this->belongsTo(Siswa::class, 'siswa_id'); }
    public function kelas(): BelongsTo { return $this->belongsTo(Kelas::class, 'kelas_id'); }
    public function jenisPelanggaran(): BelongsTo { return $this->belongsTo(JenisPelanggaran::class, 'jenis_pelanggaran_id'); }
    public function guruPelapor(): BelongsTo { return $this->belongsTo(User::class, 'guru_pelapor_id'); }
    public function dibatalkanOleh(): BelongsTo { return $this->belongsTo(User::class, 'dibatalkan_oleh_id'); }
    public function tahunAjaran(): BelongsTo { return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id'); }
    public function pembinaan(): HasMany { return $this->hasMany(PembinaanSiswa::class, 'kasus_siswa_id'); }

    /**
     * Pembinaan PALING BARU untuk kasus ini saja (1 baris) — dipakai untuk
     * kolom "Tahap" di tabel Kasus Terbaru (Dashboard BK). Pakai
     * `latestOfMany()`, BUKAN `pembinaan()->limit(1)` di eager-load closure
     * — itu jebakan Eloquent yang cuma membatasi TOTAL baris gabungan semua
     * kasus, bukan per kasus (sudah pernah kejadian di dashboard Surat).
     */
    public function pembinaanTerbaru(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PembinaanSiswa::class, 'kasus_siswa_id')->latestOfMany();
    }
    public function pemanggilan(): HasMany { return $this->hasMany(PemanggilanOrangTua::class, 'kasus_siswa_id'); }

    public function scopeAktif($query)
    {
        return $query->whereNull('dibatalkan_at');
    }

    public function getAktifAttribute(): bool
    {
        return $this->dibatalkan_at === null;
    }
}
