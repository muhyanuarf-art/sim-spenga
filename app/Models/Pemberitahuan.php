<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu pemberitahuan untuk satu orang.
 *
 * Dikirim lewat App\Support\KirimPemberitahuan, ditampilkan oleh
 * komponen <x-lonceng-pemberitahuan />.
 */
class Pemberitahuan extends Model
{
    /** Berapa banyak yang ditampilkan di dalam lonceng. */
    public const TAMPIL = 12;

    /**
     * Umur simpan. Pemberitahuan adalah penanda, bukan arsip — datanya
     * sendiri tetap ada di menu masing-masing. Yang lebih tua dari ini
     * dibersihkan `pemberitahuan:bersihkan`.
     */
    public const SIMPAN_HARI = 90;

    protected $fillable = [
        'untuk_user_id',
        'dari_user_id',
        'jenis',
        'judul',
        'isi',
        'tautan',
        'dibaca_at',
    ];

    protected $casts = [
        'dibaca_at' => 'datetime',
    ];

    /**
     * Ikon dan warna per jenis.
     *
     * Ditaruh di sini, bukan di kolom, supaya mengubah tampilan satu
     * jenis berlaku juga untuk baris lama. Warnanya dipilih yang BEDA
     * TERANGNYA, bukan hanya beda rona — merah dan hijau saja tidak
     * cukup untuk mata yang tidak bisa membedakannya.
     *
     * @var array<string, array{ikon: string, warna: string, label: string}>
     */
    private const GAYA = [
        'kasus_siswa' => [
            'ikon' => 'fa-triangle-exclamation',
            'warna' => 'bg-rose-50 text-rose-600',
            'label' => 'Kasus siswa',
        ],
        'pemanggilan_orangtua' => [
            'ikon' => 'fa-envelope-open-text',
            'warna' => 'bg-amber-50 text-amber-600',
            'label' => 'Pemanggilan orang tua',
        ],
        'prestasi_menunggu' => [
            'ikon' => 'fa-trophy',
            'warna' => 'bg-emerald-50 text-emerald-600',
            'label' => 'Prestasi menunggu verifikasi',
        ],
        'prestasi_terverifikasi' => [
            'ikon' => 'fa-circle-check',
            'warna' => 'bg-sky-50 text-sky-600',
            'label' => 'Prestasi terverifikasi',
        ],
        'nilai_final' => [
            'ikon' => 'fa-lock',
            'warna' => 'bg-violet-50 text-violet-600',
            'label' => 'Nilai difinalisasi',
        ],
        'surat_siswa' => [
            'ikon' => 'fa-envelope',
            'warna' => 'bg-indigo-50 text-indigo-600',
            'label' => 'Surat untuk siswa',
        ],
    ];

    private const GAYA_BAWAAN = [
        'ikon' => 'fa-bell',
        'warna' => 'bg-slate-100 text-slate-500',
        'label' => 'Pemberitahuan',
    ];

    public function untuk(): BelongsTo
    {
        return $this->belongsTo(User::class, 'untuk_user_id');
    }

    public function dari(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dari_user_id');
    }

    public function scopeBelumDibaca(Builder $q): Builder
    {
        return $q->whereNull('dibaca_at');
    }

    public function scopeMilik(Builder $q, int $userId): Builder
    {
        return $q->where('untuk_user_id', $userId);
    }

    public function sudahDibaca(): bool
    {
        return $this->dibaca_at !== null;
    }

    public function ikon(): string
    {
        return (self::GAYA[$this->jenis] ?? self::GAYA_BAWAAN)['ikon'];
    }

    public function warna(): string
    {
        return (self::GAYA[$this->jenis] ?? self::GAYA_BAWAAN)['warna'];
    }

    public function labelJenis(): string
    {
        return (self::GAYA[$this->jenis] ?? self::GAYA_BAWAAN)['label'];
    }

    /** "3 menit lalu", "kemarin" — lebih mudah dibaca daripada tanggal penuh. */
    public function kapan(): string
    {
        return $this->created_at->diffForHumans();
    }
}
