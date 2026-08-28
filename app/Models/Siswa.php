<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Siswa extends Model
{
    use HasFactory;

    protected $table = 'siswas';

    protected $fillable = ['nis', 'nisn', 'nama', 'nama_ortu', 'no_wa_ortu', 'jenis_kelamin', 'kelas_id', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * Orang tua login memakai NIS anaknya (lihat OrangTua & guard
     * 'orangtua'), dan NIS itu DISALIN ke tabel orang_tuas saat akun
     * dibuat. Kalau NIS siswa dikoreksi di menu Data Siswa — hal yang wajar
     * terjadi — salinannya jadi basi dan orang tua TIDAK BISA LOGIN LAGI
     * tanpa ada pesan apa pun yang menjelaskan kenapa.
     *
     * Disinkronkan lewat event model (bukan di controller) supaya berlaku
     * untuk SEMUA jalur perubahan: form Edit, Import Excel, maupun tinker.
     */
    protected static function booted(): void
    {
        static::updated(function (self $siswa) {
            if ($siswa->wasChanged('nis')) {
                OrangTua::where('siswa_id', $siswa->id)->update(['nis' => $siswa->nis]);
            }
        });
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(AbsensiSiswa::class, 'siswa_id');
    }

    public function orangTua(): HasOne
    {
        return $this->hasOne(OrangTua::class, 'siswa_id');
    }

    // ==== Modul BK ====
    public function kasusBk(): HasMany { return $this->hasMany(KasusSiswa::class, 'siswa_id'); }
    public function pembinaanBk(): HasMany { return $this->hasMany(PembinaanSiswa::class, 'siswa_id'); }
    public function penguranganPoinBk(): HasMany { return $this->hasMany(PenguranganPoinSiswa::class, 'siswa_id'); }
    public function pemanggilanOrtuBk(): HasMany { return $this->hasMany(PemanggilanOrangTua::class, 'siswa_id'); }

    /**
     * Riwayat mutasi kelas (awal masuk, kenaikan kelas antar tahun ajaran,
     * maupun pindah kelas di tengah tahun ajaran berjalan), diurutkan dari
     * periode paling awal berdasarkan tanggal efektif mutasinya.
     */
    public function riwayatKelas(): HasMany
    {
        return $this->hasMany(RiwayatKelasSiswa::class, 'siswa_id')->orderBy('tanggal_mutasi')->orderBy('id');
    }
}
