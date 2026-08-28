<?php

namespace App\Models;

use App\Support\KonteksPeriode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 1 baris pembina untuk 1 kegiatan ekstrakurikuler PADA SATU SEMESTER —
 * 1 kegiatan boleh punya banyak baris. Salah satu dari dua ini yang
 * terisi (tidak dua-duanya):
 * - `user_id`         : pembina staf sekolah (guru/guru BK/kesiswaan).
 * - `nama_eksternal`  : pembina dari LUAR sekolah, tidak punya akun sistem.
 *
 * KENAPA PER SEMESTER (2026-08-28)
 * ================================
 * Kegiatannya sendiri (Pramuka) melekat pada TAHUN ajaran — anggotanya
 * tidak perlu diisi ulang tiap semester. Tapi PEMBINANYA bisa berganti di
 * tengah tahun: guru pensiun, mutasi, atau bertukar tugas. Kalau pembina
 * ikut tahun, mengganti pembina di Semester 2 akan ikut mengubah Semester 1
 * yang sudah lewat — termasuk nama yang tercetak di rekap absensinya.
 *
 * tahun_ajaran_id di sini menunjuk baris SEMESTER-nya sendiri (bukan baris
 * Ganjil seperti master data), sama seperti GuruMengajarKelas & GuruBkKelas.
 */
class EkstrakurikulerPembina extends Model
{
    use HasFactory;

    protected $table = 'ekstrakurikuler_pembinas';

    protected $fillable = ['ekstrakurikuler_id', 'tahun_ajaran_id', 'user_id', 'nama_eksternal', 'kontak_eksternal'];

    /**
     * Isi sendiri semesternya kalau tidak ditentukan pemanggil — supaya
     * form, salin data, maupun seeder tidak bisa menghasilkan baris
     * "tanpa semester" yang tidak muncul di mana-mana.
     */
    protected static function booted(): void
    {
        static::creating(function (self $pembina) {
            if (empty($pembina->tahun_ajaran_id)) {
                $pembina->tahun_ajaran_id = TahunAjaran::aktif()?->id;
            }
        });
    }

    public function ekstrakurikuler(): BelongsTo
    {
        return $this->belongsTo(Ekstrakurikuler::class);
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }

    /** Pembina pada SEMESTER tertentu (baris tanpa semester ikut terbaca — data instalasi lama). */
    public function scopeUntukSemester($query, ?TahunAjaran $periode)
    {
        if (! $periode) {
            return $query;
        }

        return $query->where(function ($q) use ($periode) {
            $q->where('tahun_ajaran_id', $periode->id)->orWhereNull('tahun_ajaran_id');
        });
    }

    public function scopePeriodeAktif($query)
    {
        return $query->untukSemester(KonteksPeriode::pilihan());
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
