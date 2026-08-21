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

    protected $fillable = ['nama_kelas', 'tingkat', 'wali_kelas_id'];

    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'wali_kelas_id');
    }

    /** STEP 4 — histori wali kelas per tahun ajaran (lihat WaliKelasHistori). */
    public function waliKelasHistori(): HasMany
    {
        return $this->hasMany(WaliKelasHistori::class, 'kelas_id');
    }

    /**
     * Wali kelas kelas ini pada tahun ajaran tertentu (mis. "2026/2027"),
     * BUKAN wali kelas saat ini. Null kalau belum pernah tercatat untuk
     * tahun ajaran tsb (mis. tahun sebelum STEP 4 dipasang).
     */
    public function waliKelasPada(string $tahunAjaranNama): ?User
    {
        $baris = $this->waliKelasHistori()
            ->where('tahun_ajaran_nama', $tahunAjaranNama)
            ->first();

        return $baris?->waliKelas;
    }

    /**
     * STEP 4 Bagian 16 — catat snapshot wali kelas untuk tahun ajaran yang
     * SEDANG AKTIF saat ini, dipanggil setiap kali wali_kelas_id berubah
     * (lihat KelasController::update()). TIDAK menyentuh baris histori
     * tahun ajaran lain — itulah yang membuat wali kelas tahun lama tidak
     * ikut berubah saat wali kelas tahun baru diatur (Test 5).
     */
    public function catatWaliKelasHistori(string $tahunAjaranNama, ?int $waliKelasId, ?User $olehUser): void
    {
        WaliKelasHistori::updateOrCreate(
            ['kelas_id' => $this->id, 'tahun_ajaran_nama' => $tahunAjaranNama],
            ['wali_kelas_id' => $waliKelasId, 'diatur_oleh_id' => $olehUser?->id]
        );
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
}
