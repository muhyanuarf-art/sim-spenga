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

    // =================================================================
    // STATUS YANG DILIHAT PENGGUNA — sengaja hanya DUA keadaan.
    //
    // Kolom `status` di database tetap punya 4 nilai (Baru, Diproses,
    // Dalam Pembinaan, Selesai) karena dipakai untuk penyaringan &
    // laporan. Tapi di layar, guru BK cuma perlu tahu satu hal: perkara
    // ini SUDAH SELESAI atau BELUM. Empat pilihan status membuat orang
    // ragu harus memilih yang mana, dan ujungnya tidak ada yang diisi.
    //
    // Nilai antara (Diproses / Dalam Pembinaan) sekarang diisi SISTEM:
    // begitu sebuah pembinaan dicatat untuk kasus ini, statusnya otomatis
    // menjadi "Dalam Pembinaan" (lihat BkPembinaanController::store).
    // =================================================================

    public const STATUS_BARU = 'Baru';
    public const STATUS_DALAM_PEMBINAAN = 'Dalam Pembinaan';
    public const STATUS_SELESAI = 'Selesai';

    public function isSelesai(): bool
    {
        return $this->status === self::STATUS_SELESAI;
    }

    /**
     * Status yang dipakai saat kasus DIBUKA KEMBALI. Kalau sudah pernah
     * ada pembinaan, kembalikan ke "Dalam Pembinaan" (bukan "Baru") —
     * pembinaannya memang sudah berjalan, cuma dinyatakan belum tuntas.
     */
    public function statusSaatDibukaKembali(): string
    {
        return $this->pembinaan()->exists() ? self::STATUS_DALAM_PEMBINAAN : self::STATUS_BARU;
    }

    /** Label dua-keadaan untuk badge di layar. */
    public function labelStatusRingkas(): string
    {
        if ($this->dibatalkan_at) {
            return 'Dibatalkan';
        }

        return $this->isSelesai() ? 'Selesai' : 'Belum Selesai';
    }

    public function badgeStatusRingkas(): string
    {
        if ($this->dibatalkan_at) {
            return 'bg-slate-100 text-slate-400';
        }

        return $this->isSelesai() ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700';
    }

    public function scopeAktif($query)
    {
        return $query->whereNull('dibatalkan_at');
    }

    /** Kasus yang masih perlu ditindaklanjuti (belum selesai & belum dibatalkan). */
    public function scopeBelumSelesai($query)
    {
        return $query->aktif()->where('status', '!=', self::STATUS_SELESAI);
    }

    public function getAktifAttribute(): bool
    {
        return $this->dibatalkan_at === null;
    }
}
