<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PembinaanSiswa extends Model
{
    protected $fillable = [
        'siswa_id', 'kasus_siswa_id', 'tahun_ajaran_id', 'tanggal', 'tahap',
        'jenis_pembinaan', 'catatan_bk', 'hasil_pembinaan', 'bukti_file', 'status',
        'tanggal_evaluasi_berikutnya', 'ruang_refleksi_selesai', 'petugas_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'tanggal_evaluasi_berikutnya' => 'date',
            'ruang_refleksi_selesai' => 'date',
        ];
    }

    public function getBuktiFileUrlAttribute(): ?string
    {
        return $this->bukti_file ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->bukti_file) : null;
    }

    // Dua keadaan yang dilihat pengguna — sama seperti KasusSiswa, supaya
    // guru BK tidak perlu menghafal istilah status yang berbeda-beda antar
    // halaman. "Pembinaan" ditampilkan sebagai "Belum Selesai".
    public const STATUS_BERJALAN = 'Pembinaan';
    public const STATUS_SELESAI = 'Selesai';

    /**
     * Pilihan jenis pembinaan — HARUS sama persis dengan enum kolom
     * `jenis_pembinaan` di database.
     *
     * (2026-08-28) Dulu daftar ini cuma ditulis di dalam view, sedangkan
     * validasinya hanya ['required','string']. Nilai di luar daftar lolos
     * validasi lalu ditolak database ("Data truncated") dan berakhir
     * sebagai HTTP 500. Sekarang satu daftar dipakai bersama oleh form dan
     * validasi, jadi tidak mungkin lagi berbeda.
     */
    public const JENIS_LIST = [
        'Teguran lisan',
        'Teguran tertulis',
        'Penugasan edukatif',
        'Konseling individu',
        'Kontrak perilaku',
        'Pemanggilan orang tua',
        'Pembinaan khusus',
        'Ruang refleksi',
        'Skorsing edukatif',
        'Pembinaan lanjutan',
    ];

    public function isSelesai(): bool
    {
        return $this->status === self::STATUS_SELESAI;
    }

    public function labelStatusRingkas(): string
    {
        return $this->isSelesai() ? 'Selesai' : 'Belum Selesai';
    }

    public function badgeStatusRingkas(): string
    {
        return $this->isSelesai() ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700';
    }

    public function siswa(): BelongsTo { return $this->belongsTo(Siswa::class, 'siswa_id'); }
    public function kasus(): BelongsTo { return $this->belongsTo(KasusSiswa::class, 'kasus_siswa_id'); }
    public function petugas(): BelongsTo { return $this->belongsTo(User::class, 'petugas_id'); }
    public function tahunAjaran(): BelongsTo { return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id'); }
    public function evaluasiHarian(): HasMany { return $this->hasMany(EvaluasiPembinaan::class, 'pembinaan_siswa_id')->orderBy('hari_ke'); }
}
