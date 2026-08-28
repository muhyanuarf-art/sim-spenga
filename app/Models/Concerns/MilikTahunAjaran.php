<?php

namespace App\Models\Concerns;

use App\Models\TahunAjaran;
use App\Support\KonteksPeriode;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dipakai model MASTER DATA yang berlaku per TAHUN AJARAN: Mata Pelajaran,
 * Jam Pelajaran, Jenis Pelanggaran, Jenis Surat, dan Ekstrakurikuler.
 *
 * Dua hal yang dikerjakan trait ini:
 *
 * 1. MENGISI SENDIRI tahun_ajaran_id saat baris dibuat, KALAU belum diisi.
 *    Lewat event 'creating' supaya tidak ada satu pun jalur pembuatan data
 *    (form, import Excel, seeder) yang bisa lupa mengisinya lalu
 *    menghasilkan baris "tanpa periode" yang tidak muncul di mana-mana.
 *
 *    Kolomnya TETAP ada di $fillable masing-masing model, karena satu
 *    pemakai memang perlu menentukannya sendiri: App\Support\SalinDataPeriode
 *    menulis ke periode TUJUAN, yang saat itu belum tentu periode aktif.
 *
 * 2. MENYEDIAKAN SCOPE BACA yang seragam. Konvensinya sama persis dengan
 *    tabel kelas: nilai yang disimpan SELALU id baris SEMESTER GANJIL,
 *    karena master data berlaku untuk satu tahun penuh. Kalau periode yang
 *    sedang aktif kebetulan Semester Genap, scope mencarikan sendiri baris
 *    Ganjil pasangannya.
 *
 * CATATAN PENTING soal baris "tanpa periode" (tahun_ajaran_id NULL):
 * baris seperti itu TETAP IKUT TERBACA oleh scope. Keadaan itu cuma
 * terjadi pada instalasi yang sama sekali belum punya tahun ajaran —
 * kalau baris NULL disembunyikan, aplikasi akan tampak kosong total dan
 * admin tidak punya jalan masuk untuk memperbaikinya.
 */
trait MilikTahunAjaran
{
    protected static function bootMilikTahunAjaran(): void
    {
        static::creating(function ($model) {
            if (empty($model->tahun_ajaran_id)) {
                $model->tahun_ajaran_id = static::idPeriodeAktif();
            }
        });
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }

    /** Data milik SATU SEMESTER tertentu. */
    public function scopeUntukTahunAjaran($query, ?TahunAjaran $tahunAjaran)
    {
        if (! $tahunAjaran) {
            return $query;
        }

        return $query->where(function ($q) use ($tahunAjaran) {
            $q->where('tahun_ajaran_id', $tahunAjaran->id)->orWhereNull('tahun_ajaran_id');
        });
    }

    /**
     * Data milik periode yang SEDANG AKTIF — dipakai hampir di semua
     * halaman. Halaman histori yang sengaja ingin melihat periode lain
     * harus memanggil untukTahunAjaran() dengan periode pilihannya.
     */
    public function scopePeriodeAktif($query)
    {
        return $query->untukTahunAjaran(KonteksPeriode::pilihan());
    }

    /**
     * id SEMESTER yang sedang berjalan — dipakai saat menyimpan.
     *
     * Sengaja memakai periode yang benar-benar aktif, BUKAN periode
     * pilihan: pencatatan baru selalu masuk ke semester berjalan, dan
     * menyimpan saat sedang menengok periode lampau sudah diblokir lebih
     * dulu oleh middleware 'periode-aktif'.
     */
    public static function idPeriodeAktif(): ?int
    {
        return TahunAjaran::aktif()?->id;
    }
}
