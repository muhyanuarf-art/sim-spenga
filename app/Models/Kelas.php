<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Support\KonteksPeriode;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Kelas extends Model
{
    use HasFactory;

    protected $table = 'kelas';

    public const STATUS_AKTIF = 'aktif';
    public const STATUS_NONAKTIF = 'nonaktif';

    protected $fillable = ['tahun_ajaran_id', 'nama_kelas', 'tingkat', 'status'];

    /**
     * STEP 5 Bagian 4 — setiap baris kelas terikat SATU tahun ajaran.
     * Konvensi: SELALU baris Semester GANJIL (lihat catatan lengkap di
     * migrasi 2026_08_20_000005_add_tahun_ajaran_to_kelas_table.php).
     */
    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }

    /** Seluruh penugasan wali kelas untuk kelas ini, lintas semester. */
    public function penugasanWali(): HasMany
    {
        return $this->hasMany(PenugasanWaliKelas::class, 'kelas_id');
    }

    /**
     * WALI KELAS PADA PERIODE AKTIF.
     *
     * Dulu ini kolom `kelas.wali_kelas_id`. Karena baris kelas dipakai
     * bersama Semester Ganjil & Genap, mengganti wali kelas di Semester 2
     * ikut mengubah Semester 1 — lihat App\Models\PenugasanWaliKelas.
     *
     * Bentuknya sengaja tetap "satu relasi yang menghasilkan User", supaya
     * seluruh pemakaian lama ($kelas->waliKelas->name, with('waliKelas'),
     * blok tanda tangan pada lembar cetak) tidak perlu diubah sama sekali.
     * Untuk periode selain yang aktif, pakai waliKelasPada().
     */
    public function waliKelas(): HasOneThrough
    {
        // Periode PILIHAN, bukan periode aktif — lihat App\Support\KonteksPeriode.
        $aktif = KonteksPeriode::pilihan();

        return $this->hasOneThrough(
            User::class,
            PenugasanWaliKelas::class,
            'kelas_id',  // kunci di penugasan yang menunjuk kelas
            'id',        // kunci di users
            'id',        // kunci lokal di kelas
            'guru_id'    // kunci di penugasan yang menunjuk users
        )->when(
            $aktif,
            fn ($q) => $q->where('penugasan_wali_kelas.tahun_ajaran_id', $aktif->id),
            fn ($q) => $q->whereRaw('1 = 0')
        );
    }

    /** Wali kelas pada SEMESTER tertentu — dipakai laporan periode lampau. */
    public function waliKelasPada(?TahunAjaran $periode): ?User
    {
        if (! $periode) {
            return null;
        }

        return PenugasanWaliKelas::where('tahun_ajaran_id', $periode->id)
            ->where('kelas_id', $this->id)
            ->first()?->guru;
    }

    /** Seluruh baris keanggotaan kelas ini. */
    public function anggota(): HasMany
    {
        return $this->hasMany(AnggotaKelas::class, 'kelas_id');
    }

    /**
     * SISWA ANGGOTA KELAS INI.
     *
     * Sejak 29 Agustus 2026 keanggotaan disimpan per SEMESTER di tabel
     * anggota_kelas — kelas ini sendiri sudah milik satu semester, jadi
     * daftarnya tidak perlu disaring periode lagi. Bentuknya dipertahankan
     * sebagai relasi berisi Siswa supaya pemakaian lama ($kelas->siswas,
     * withCount('siswas'), with('siswas')) tetap jalan apa adanya.
     */
    public function siswas(): BelongsToMany
    {
        return $this->belongsToMany(Siswa::class, 'anggota_kelas', 'kelas_id', 'siswa_id')
            ->withTimestamps();
    }

    public function guruMengajar(): HasMany
    {
        return $this->hasMany(GuruMengajarKelas::class, 'kelas_id');
    }

    /**
     * Fase Kurikulum Merdeka untuk tingkat kelas ini. Dipakai di kepala
     * lembar Daftar Nilai & laporan penilaian ("Kelas/Fase : IX A / D").
     * Jenjang SMP (kelas 7, 8, 9) seluruhnya berada di Fase D.
     */
    public function fase(): string
    {
        return match ((int) $this->tingkat) {
            7, 8, 9 => 'D',
            default => '-',
        };
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
     *
     * PERBAIKAN PERFORMA — sebelumnya memakai whereHas('tahunAjaran', ...)
     * yang menghasilkan SUBQUERY EXISTS berkorelasi (lebih mahal untuk
     * database). Method ini dipanggil SANGAT SERING lewat Kelas::aktif()
     * (dashboard, dropdown kelas di hampir semua halaman). Sekarang
     * langsung WHERE tahun_ajaran_id = ... (index biasa, jauh lebih murah)
     * — kelas.tahun_ajaran_id SELALU baris Semester GANJIL (lihat catatan
     * migrasi), jadi kalau $tahunAjaran yang diberikan Semester Genap,
     * cari dulu id baris Ganjil pasangannya (1x lookup, di-cache per
     * nama supaya tidak query ulang kalau dipanggil berkali-kali dalam 1
     * request yang sama).
     */
    public function scopeUntukTahunAjaran($query, TahunAjaran $tahunAjaran)
    {
        // Kelas kini milik SATU SEMESTER (migrasi 2026_08_29_000001),
        // jadi tidak ada lagi pencarian baris Ganjil.
        //
        // Nama tabel ditulis lengkap karena scope ini juga dipakai di dalam
        // whereHas('kelas', ...) dari Siswa — di sana query-nya ikut
        // menggabungkan anggota_kelas yang punya kolom tahun_ajaran_id juga.
        return $query->where('kelas.tahun_ajaran_id', $tahunAjaran->id);
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
        $periodeAktif = KonteksPeriode::pilihan();
        if (! $periodeAktif) {
            return $query->whereRaw('1 = 0');
        }

        return $query->untukTahunAjaran($periodeAktif);
    }
}
