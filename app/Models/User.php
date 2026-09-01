<?php

namespace App\Models;

use App\Support\KonteksPeriode;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Kata sandi yang dipasang saat akun direset — lewat menu Admin
     * maupun lewat OTP WhatsApp di aplikasi Android. Nilainya sama
     * dengan milik portal orang tua supaya hanya ada satu yang perlu
     * diingat saat menuntun guru lewat telepon.
     */
    public const PASSWORD_DEFAULT = 'password';

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
    public function isTu(): bool { return $this->role === 'tu'; }
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
     * (2026-08-28) Sumbernya pindah dari kolom `kelas.wali_kelas_id` ke
     * tabel penugasan_wali_kelas, dan penyaringnya kini SEMESTER AKTIF —
     * bukan lagi tahun ajaran. Guru yang menggantikan wali kelas mulai
     * Semester 2 tidak ikut tercatat sebagai wali kelas Semester 1.
     * Lihat App\Models\PenugasanWaliKelas.
     */
    public function kelasWali(): HasOneThrough
    {
        // Periode PILIHAN, bukan periode aktif — guru yang menengok
        // semester lampau kembali dikenali sebagai wali kelas pada
        // semester itu (App\Support\KonteksPeriode).
        $periodeAktif = KonteksPeriode::pilihan();

        return $this->hasOneThrough(
            Kelas::class,
            PenugasanWaliKelas::class,
            'guru_id',   // kunci di penugasan yang menunjuk users
            'id',        // kunci di kelas
            'id',        // kunci lokal di users
            'kelas_id'   // kunci di penugasan yang menunjuk kelas
        )->when(
            $periodeAktif,
            fn ($q) => $q->where('penugasan_wali_kelas.tahun_ajaran_id', $periodeAktif->id),
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

        $tahunAjaran = KonteksPeriode::pilihan();
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
            'tu' => 'Tata Usaha',
            'guru' => $this->isWaliKelas() ? 'Guru / Wali Kelas' : 'Guru Mapel',
            default => ucfirst($this->role),
        };
    }
}
