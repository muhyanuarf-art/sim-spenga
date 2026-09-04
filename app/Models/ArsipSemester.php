<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Satu berkas arsip untuk satu semester.
 *
 * Lihat migrasi create_arsip_semesters_table untuk alasan lengkapnya —
 * terutama mengapa statusnya bisa 'kedaluwarsa' dan mengapa arsip lama
 * tidak dihapus saat itu terjadi.
 */
class ArsipSemester extends Model
{
    protected $table = 'arsip_semesters';

    protected $fillable = [
        'tahun_ajaran_id', 'path', 'ukuran', 'jumlah_berkas',
        'status', 'catatan', 'dibuat_oleh', 'selesai_at',
    ];

    protected function casts(): array
    {
        return ['selesai_at' => 'datetime'];
    }

    /** Berapa lama arsip disimpan sebelum dihapus sendiri. */
    public const SIMPAN_BULAN = 12;

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function bisaDiunduh(): bool
    {
        return in_array($this->status, ['siap', 'kedaluwarsa'], true)
            && $this->path
            && Storage::disk('local')->exists($this->path);
    }

    public function sedangDikerjakan(): bool
    {
        return $this->status === 'antre';
    }

    /**
     * Kalimat keadaan yang bisa dibaca Admin — bukan sekadar nama status.
     * Yang penting baginya bukan "kedaluwarsa", melainkan APA yang harus
     * ia lakukan sekarang.
     */
    public function keterangan(): string
    {
        return match ($this->status) {
            'antre' => 'Sedang dibuat. Halaman ini akan menampilkan tombol unduh begitu selesai.',
            'siap' => 'Siap diunduh. Isinya sesuai dengan data semester ini.',
            'kedaluwarsa' => 'Dibuat SEBELUM semester ini dibuka kunci. Isinya mungkin sudah '
                .'tidak sesuai dengan data sekarang — buat ulang bila akan dipakai untuk laporan resmi.',
            'gagal' => 'Pembuatan gagal: '.($this->catatan ?: 'sebab tidak diketahui').'.',
            default => '',
        };
    }

    public function ukuranTerbaca(): string
    {
        if (! $this->ukuran) {
            return '—';
        }

        return $this->ukuran >= 1048576
            ? round($this->ukuran / 1048576, 1).' MB'
            : round($this->ukuran / 1024).' KB';
    }

    /** Kapan berkas ini dihapus sendiri oleh perawatan berkala. */
    public function dihapusPada(): ?\Illuminate\Support\Carbon
    {
        return $this->selesai_at?->copy()->addMonths(self::SIMPAN_BULAN);
    }
}
