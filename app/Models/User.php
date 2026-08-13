<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'nip', 'email', 'password', 'role', 'no_hp', 'is_active',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // ==== Role helpers ====
    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isKepalaSekolah(): bool { return $this->role === 'kepala_sekolah'; }
    public function isKurikulum(): bool { return $this->role === 'kurikulum'; }
    public function isGuru(): bool { return $this->role === 'guru'; }
    public function isGuruBk(): bool { return $this->role === 'guru_bk'; }
    public function isOrangTua(): bool { return $this->role === 'orang_tua'; }
    public function isWaliKelas(): bool { return $this->kelasWali()->exists(); }

    // ==== Relations ====
    public function kelasWali(): HasOne
    {
        return $this->hasOne(Kelas::class, 'wali_kelas_id');
    }

    public function mengajarKelas(): HasMany
    {
        return $this->hasMany(GuruMengajarKelas::class, 'guru_id');
    }

    public function bkKelas(): HasMany
    {
        return $this->hasMany(GuruBkKelas::class, 'guru_id');
    }

    /** Daftar Kelas yang dipantau Guru BK ini pada tahun ajaran aktif. */
    public function kelasBk()
    {
        $tahunAjaran = TahunAjaran::aktif();
        if (! $tahunAjaran) {
            return collect();
        }
        return Kelas::whereIn('id', $this->bkKelas()->where('tahun_ajaran_id', $tahunAjaran->id)->pluck('kelas_id'))
            ->orderBy('nama_kelas')
            ->get();
    }

    public function jadwalMengajar(): HasMany
    {
        return $this->hasMany(JadwalPelajaran::class, 'guru_id');
    }

    public function jurnalMengajar(): HasMany
    {
        return $this->hasMany(JurnalMengajar::class, 'guru_id');
    }

    /** Anak (siswa) yang ditautkan ke akun Orang Tua ini. */
    public function anakAsuh(): BelongsToMany
    {
        return $this->belongsToMany(Siswa::class, 'orang_tua_siswa', 'user_id', 'siswa_id')
            ->withPivot('hubungan')
            ->withTimestamps();
    }

    /** Cek apakah akun Orang Tua ini ditautkan ke siswa tertentu. */
    public function bisaAksesAnak(Siswa $siswa): bool
    {
        return $this->anakAsuh()->where('siswas.id', $siswa->id)->exists();
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            'admin' => 'Administrator',
            'kepala_sekolah' => 'Kepala Sekolah',
            'kurikulum' => 'Kurikulum',
            'guru_bk' => 'Guru BK',
            'orang_tua' => 'Orang Tua/Wali Siswa',
            'guru' => $this->isWaliKelas() ? 'Guru / Wali Kelas' : 'Guru Mapel',
            default => ucfirst($this->role),
        };
    }
}
