<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'nip', 'email', 'password', 'role', 'no_hp', 'is_active',
    ];

    /** Cache instance kelasBk() per-request — lihat method kelasBk() di bawah. */
    private ?\Illuminate\Support\Collection $cachedKelasBk = null;

    /** Cache instance isWaliKelas() per-request — lihat method isWaliKelas() di bawah. */
    private ?bool $cachedIsWaliKelas = null;

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
    public function isKesiswaan(): bool { return $this->role === 'kesiswaan'; }
    /**
     * STEP 5 Bagian 10 & 23 — "wali kelas" berarti wali kelas untuk TAHUN
     * AJARAN AKTIF (lihat kelasWali()).
     *
     * PERBAIKAN PERFORMA — dipanggil ≥2x per request untuk role guru
     * (sidebar yang tampil di SETIAP halaman + roleLabel() + middleware
     * EnsureWaliKelas di rute yang relevan), dan sebelumnya SETIAP
     * panggilan = query baru (kelasWali() dipanggil sebagai METHOD di sini,
     * bukan property, jadi tidak otomatis kena cache relasi Eloquent).
     * Sekarang di-cache di instance User yang sama, sama seperti kelasBk().
     */
    public function isWaliKelas(): bool
    {
        return $this->cachedIsWaliKelas ??= $this->kelasWali()->exists();
    }

    // ==== Relations ====
    /**
     * STEP 5 Bagian 10 & 23 — kelas ini otomatis ter-scope ke TAHUN AJARAN
     * YANG SEDANG AKTIF sekarang (bukan sembarang tahun terakhir user pernah
     * jadi wali kelas). Sengaja di-scope DI SINI (di definisi relasi), bukan
     * di tiap tempat yang memakainya — supaya semua pemanggil lama
     * ($user->kelasWali, isWaliKelas(), BkAccessScope, Dashboard,
     * NotifikasiWhatsappController, dst) otomatis benar tanpa perlu diubah
     * satu per satu.
     *
     * PERBAIKAN PERFORMA — sebelumnya whereHas('tahunAjaran', ...)
     * (EXISTS subquery). Sekarang WHERE tahun_ajaran_id = ... langsung
     * lewat TahunAjaran::idSemesterGanjilUntukNama() (lihat catatan di
     * method itu) — index biasa, jauh lebih murah, dan method ini
     * dipanggil di sidebar yang tampil di SETIAP halaman untuk role Guru.
     */
    public function kelasWali(): HasOne
    {
        $periodeAktif = TahunAjaran::aktif();
        $idGanjil = $periodeAktif ? TahunAjaran::idSemesterGanjilUntukNama($periodeAktif->nama) : null;

        return $this->hasOne(Kelas::class, 'wali_kelas_id')
            ->when(
                $idGanjil,
                fn ($q) => $q->where('tahun_ajaran_id', $idGanjil),
                fn ($q) => $q->whereRaw('1 = 0')
            );
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
        // PERBAIKAN PERFORMA — sebelumnya method ini menjalankan 2 query
        // BARU setiap kali dipanggil. Dipanggil ≥2x per request di hampir
        // semua controller BK (sekali lewat BkAccessScope::bkKelasIdsUntukUser(),
        // sekali lagi langsung di controller-nya) — di-cache di instance
        // User yang sama supaya cuma jalan sekali per request (auth()->user()
        // mengembalikan instance User yang SAMA sepanjang 1 request).
        if ($this->cachedKelasBk !== null) {
            return $this->cachedKelasBk;
        }

        $tahunAjaran = TahunAjaran::aktif();
        if (! $tahunAjaran) {
            return $this->cachedKelasBk = collect();
        }
        return $this->cachedKelasBk = Kelas::whereIn('id', $this->bkKelas()->where('tahun_ajaran_id', $tahunAjaran->id)->pluck('kelas_id'))
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

    public function roleLabel(): string
    {
        return match ($this->role) {
            'admin' => 'Administrator',
            'kepala_sekolah' => 'Kepala Sekolah',
            'kurikulum' => 'Kurikulum',
            'guru_bk' => 'Guru BK',
            'kesiswaan' => 'Kesiswaan',
            'guru' => $this->isWaliKelas() ? 'Guru / Wali Kelas' : 'Guru Mapel',
            default => ucfirst($this->role),
        };
    }
}
