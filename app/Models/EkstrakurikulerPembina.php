<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 1 baris pembina untuk 1 kegiatan ekstrakurikuler — 1 kegiatan boleh
 * punya banyak baris. Salah satu dari dua ini yang terisi (tidak dua-duanya):
 * - `user_id`         : pembina staf sekolah (guru/guru BK/kesiswaan).
 * - `nama_eksternal`  : pembina dari LUAR sekolah, tidak punya akun sistem.
 */
class EkstrakurikulerPembina extends Model
{
    use HasFactory;

    protected $table = 'ekstrakurikuler_pembinas';

    protected $fillable = ['ekstrakurikuler_id', 'user_id', 'nama_eksternal', 'kontak_eksternal'];

    public function ekstrakurikuler(): BelongsTo
    {
        return $this->belongsTo(Ekstrakurikuler::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isEksternal(): bool
    {
        return $this->user_id === null;
    }

    /** Nama tampil, apa pun sumbernya (internal/eksternal). */
    public function namaTampil(): string
    {
        return $this->user->name ?? $this->nama_eksternal ?? '-';
    }
}
