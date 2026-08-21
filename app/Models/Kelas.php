<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kelas extends Model
{
    use HasFactory;

    protected $table = 'kelas';

    public const STATUS_AKTIF = 'aktif';
    public const STATUS_NONAKTIF = 'nonaktif';

    protected $fillable = ['tahun_ajaran_id', 'nama_kelas', 'tingkat', 'wali_kelas_id', 'status'];

    /**
     * STEP 5 Bagian 4 — setiap baris kelas terikat SATU tahun ajaran.
     * Konvensi: SELALU baris Semester GANJIL (lihat catatan lengkap di
     * migrasi 2026_08_20_000005_add_tahun_ajaran_to_kelas_table.php).
     */
    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }

    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'wali_kelas_id');
    }

    public function siswas(): HasMany
    {
        return $this->hasMany(Siswa::class, 'kelas_id');
    }

    public function guruMengajar(): HasMany
    {
        return $this->hasMany(GuruMengajarKelas::class, 'kelas_id');
    }

    public function jadwal(): HasMany
    {
        return $this->hasMany(JadwalPelajaran::class, 'kelas_id');
    }

    /**
     * STEP 5 Bagian 23 — SATU sumber utama untuk "kelas pada tahun ajaran
     * X", dipakai di seluruh app (dashboard, jadwal, guru mengajar, wali
     * kelas, BK, dst) supaya query-nya konsisten di satu tempat. Cocokkan
     * lewat NAMA (bukan tahun_ajaran_id persis) — lihat catatan konvensi
     * di migrasi.
     */
    public function scopeUntukTahunAjaran($query, TahunAjaran $tahunAjaran)
    {
        return $query->whereHas('tahunAjaran', fn ($q) => $q->where('nama', $tahunAjaran->nama));
    }

    /** Sama seperti scopeUntukTahunAjaran(), tapi menerima ID tahun ajaran (dipakai import Excel). */
    public function scopeUntukTahunAjaranId($query, int $tahunAjaranId)
    {
        $tahunAjaran = TahunAjaran::find($tahunAjaranId);
        if (! $tahunAjaran) {
            return $query->whereRaw('1 = 0');
        }

        return $query->untukTahunAjaran($tahunAjaran);
    }

    /**
     * STEP 5 Bagian 23 — kelas pada TAHUN AJARAN YANG SEDANG AKTIF. Ini
     * default untuk hampir semua halaman (dashboard, dropdown pilih kelas,
     * dll) — halaman histori yang sengaja butuh tahun ajaran lain harus
     * eksplisit memakai scopeUntukTahunAjaran() dengan periode pilihan.
     */
    public function scopeAktif($query)
    {
        $periodeAktif = TahunAjaran::aktif();
        if (! $periodeAktif) {
            return $query->whereRaw('1 = 0');
        }

        return $query->untukTahunAjaran($periodeAktif);
    }
}
