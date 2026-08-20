<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TahunAjaran extends Model
{
    use HasFactory;

    /**
     * STEP 1 — status adalah label siklus hidup periode untuk TAMPILAN
     * admin saja (Bagian 3 & 4): akan_datang | aktif | selesai.
     * Ini TERPISAH dari `terkunci` (lock, lingkup STEP 2) dan tidak
     * menggantikan `is_active` sebagai sumber kebenaran periode aktif —
     * lihat static::aktif() & App\Support\PeriodeAkademik.
     */
    public const STATUS_AKAN_DATANG = 'akan_datang';
    public const STATUS_AKTIF = 'aktif';
    public const STATUS_SELESAI = 'selesai';

    protected $fillable = [
        'nama', 'semester', 'tanggal_mulai', 'tanggal_selesai', 'status',
        'is_active', 'diaktifkan_at', 'diaktifkan_oleh_id',
        'terkunci', 'terkunci_at', 'terkunci_oleh_id',
        'dibuka_at', 'dibuka_oleh_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'is_active' => 'boolean',
            'diaktifkan_at' => 'datetime',
            'terkunci' => 'boolean',
            'terkunci_at' => 'datetime',
            'dibuka_at' => 'datetime',
        ];
    }

    public function terkunciOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'terkunci_oleh_id');
    }

    /** STEP 2 Bagian 10: siapa yang terakhir membuka kembali periode ini. */
    public function dibukaOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuka_oleh_id');
    }

    /** STEP 3 Bagian 16: siapa yang terakhir mengaktifkan periode ini. */
    public function diaktifkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diaktifkan_oleh_id');
    }

    public static function aktif(): ?self
    {
        return static::where('is_active', true)->first();
    }

    /**
     * Tahap 2 (kunci periode). Dipakai App\Http\Middleware\EnsurePeriodeTidakTerkunci
     * untuk memblokir aksi tulis (jurnal, absensi, modul BK) HANYA kalau
     * periode yang terkunci adalah periode yang sedang AKTIF.
     */
    public function isTerkunci(): bool
    {
        return (bool) $this->terkunci;
    }

    /**
     * STEP 2 Bagian 5 & 6 — Tutup Semester: menandai periode SELESAI +
     * TERKUNCI dalam 1 aksi atomik (dipanggil di dalam DB::transaction
     * oleh controller). Tidak mengubah is_active — periode berikutnya
     * TIDAK otomatis aktif (itu STEP 3), dan pergantian is_active tetap
     * murni tanggung jawab TahunAjaranController::aktifkan().
     */
    public function tutup(\App\Models\User $olehUser): void
    {
        $this->update([
            'status' => self::STATUS_SELESAI,
            'terkunci' => true,
            'terkunci_at' => now(),
            'terkunci_oleh_id' => $olehUser->id,
        ]);
    }

    /**
     * STEP 2 Bagian 10 — Buka Kembali: hanya melepas kunci tulis. Kolom
     * `status` SENGAJA tidak dikembalikan ke 'akan_datang'/'aktif' —
     * secara kronologis periode ini tetap "sudah terjadi/selesai" walau
     * datanya sekarang bisa diedit lagi. Badge "🔓 Terbuka" di kolom Kunci
     * pada halaman admin sudah cukup menunjukkan bahwa datanya kini bisa
     * diubah, tanpa perlu status kedua yang artinya tumpang tindih dengan
     * `terkunci` (lihat catatan desain di migrasi STEP 1).
     */
    public function bukaKembali(\App\Models\User $olehUser): void
    {
        $this->update([
            'terkunci' => false,
            'dibuka_at' => now(),
            'dibuka_oleh_id' => $olehUser->id,
        ]);
    }

    /**
     * STEP 3 Bagian 13.A/13.C — Semester Genap dalam tahun ajaran yang SAMA
     * (bukan tahun ajaran berikutnya — itu STEP 4). Null kalau baris ini
     * bukan Semester Ganjil, atau kalau Semester Genap-nya belum dibuat.
     */
    public function semesterBerikutnya(): ?self
    {
        if ($this->semester !== 'Ganjil') {
            return null;
        }

        return static::where('nama', $this->nama)
            ->where('semester', 'Genap')
            ->first();
    }

    /**
     * STEP 3 Bagian 13 — Apakah tombol "Tutup Semester & Aktifkan Semester
     * Berikutnya" boleh ditampilkan/dijalankan untuk baris ini. SEMUA
     * syarat berikut harus terpenuhi:
     * - baris ini sedang AKTIF (bukan sekadar Ganjil yang belum aktif)
     * - baris ini Semester Ganjil (Genap→tahun ajaran baru = STEP 4)
     * - Semester Genap pasangannya sudah ada
     * - Semester Genap pasangannya BELUM aktif (cegah proses ganda)
     */
    public function bisaGantiSemester(): bool
    {
        if (! $this->is_active || $this->semester !== 'Ganjil') {
            return false;
        }

        $berikutnya = $this->semesterBerikutnya();

        return $berikutnya !== null && ! $berikutnya->is_active;
    }

    /**
     * Tahap 3. Label gabungan untuk ditampilkan di UI, contoh:
     * "Tahun Ajaran 2026/2027 — Semester Ganjil".
     *
     * Ini HANYA perubahan tampilan (label), bukan perubahan skema.
     * Struktur tabel tahun_ajarans dipertahankan apa adanya (1 baris = 1
     * kombinasi tahun ajaran + semester) sesuai alternatif yang lebih
     * murah/rendah risiko dibanding memecah jadi tabel TahunAjaran + Semester
     * terpisah.
     */
    public function labelPeriode(): string
    {
        return "Tahun Ajaran {$this->nama} — Semester {$this->semester}";
    }

    /**
     * Versi singkat untuk tempat yang ruangnya terbatas (mis. badge header),
     * contoh: "2026/2027 · Semester Ganjil".
     */
    public function labelSingkat(): string
    {
        return "{$this->nama} · Semester {$this->semester}";
    }

    /**
     * Label status siklus hidup untuk ditampilkan di admin (Bagian 3, 4, 7,
     * dan STEP 2 Bagian 6/19).
     *
     * Prioritas SENGAJA: terkunci > is_active > status tersimpan. Setelah
     * "Tutup Semester" (STEP 2), is_active TIDAK diubah (semester
     * berikutnya belum otomatis aktif — itu STEP 3), jadi kalau is_active
     * dicek duluan badge akan salah tetap bilang "Aktif" padahal periode
     * sudah ditutup. `terkunci` adalah sinyal paling definitif bahwa
     * periode ini sudah selesai & read-only, jadi itu dicek lebih dulu.
     */
    public function statusLabel(): string
    {
        if ($this->terkunci) {
            return 'Selesai';
        }

        if ($this->is_active) {
            return 'Aktif';
        }

        return match ($this->status) {
            self::STATUS_SELESAI => 'Selesai',
            self::STATUS_AKTIF => 'Akan Datang', // aktif tersimpan tapi is_active sudah false → sudah tidak berlaku
            default => 'Akan Datang',
        };
    }

    /** Kelas badge Tailwind untuk statusLabel(), dipakai di view. */
    public function statusBadgeClass(): string
    {
        if ($this->terkunci) {
            return 'bg-slate-100 text-slate-500';
        }

        if ($this->is_active) {
            return 'bg-emerald-50 text-emerald-700';
        }

        return $this->status === self::STATUS_SELESAI
            ? 'bg-slate-100 text-slate-500'
            : 'bg-amber-50 text-amber-700';
    }

    /** Rentang tanggal untuk ditampilkan, mis. "13 Jul 2026 – 20 Jun 2027". */
    public function rentangTanggal(): ?string
    {
        if (! $this->tanggal_mulai || ! $this->tanggal_selesai) {
            return null;
        }

        return $this->tanggal_mulai->translatedFormat('d M Y').' – '.$this->tanggal_selesai->translatedFormat('d M Y');
    }
}
