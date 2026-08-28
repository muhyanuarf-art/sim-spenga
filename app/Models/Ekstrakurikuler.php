<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\MilikTahunAjaran;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ekstrakurikuler extends Model
{
    use HasFactory;
    // Master data per tahun ajaran — lihat trait & migrasi 2026_08_28_000003.
    use MilikTahunAjaran;

    protected $table = 'ekstrakurikulers';

    protected $fillable = ['tahun_ajaran_id', 'nama_ekstrakurikuler', 'keterangan', 'is_aktif'];

    protected function casts(): array
    {
        return ['is_aktif' => 'boolean'];
    }

    /**
     * (2026-08-23, revisi) — bisa punya BANYAK pembina, campuran staf
     * sekolah (user_id terisi) maupun dari luar sekolah (nama_eksternal
     * terisi, tidak punya akun). Lihat App\Models\EkstrakurikulerPembina.
     */
    public function pembinas(): HasMany
    {
        // Disaring ke SEMESTER AKTIF: pembina bisa berganti di tengah tahun
        // (pensiun/mutasi) dan semester yang sudah lewat tidak boleh ikut
        // berubah — lihat App\Models\EkstrakurikulerPembina. Untuk melihat
        // seluruh semester sekaligus, pakai semuaPembinas().
        return $this->hasMany(EkstrakurikulerPembina::class)->periodeAktif();
    }

    /** Pembina lintas semester — dipakai rekap/laporan periode lampau. */
    public function semuaPembinas(): HasMany
    {
        return $this->hasMany(EkstrakurikulerPembina::class);
    }

    /** Pembina pada SEMESTER tertentu. */
    public function pembinasPada(?TahunAjaran $periode): HasMany
    {
        return $this->hasMany(EkstrakurikulerPembina::class)->untukSemester($periode);
    }

    /** Anggota (siswa) kegiatan ini — lihat App\Models\EkstrakurikulerSiswa. */
    public function anggotas(): HasMany
    {
        return $this->hasMany(EkstrakurikulerSiswa::class);
    }

    /** Sesi absensi kegiatan ini — lihat App\Models\AbsensiEkskul. */
    public function absensis(): HasMany
    {
        return $this->hasMany(AbsensiEkskul::class);
    }

    /** Daftar nama pembina (internal + eksternal) digabung, untuk tampilan ringkas. */
    public function daftarNamaPembina(): string
    {
        $nama = $this->pembinas->map(fn ($p) => $p->namaTampil() . ($p->isEksternal() ? ' (luar sekolah)' : ''));
        return $nama->isEmpty() ? '—' : $nama->implode(', ');
    }

    /**
     * Apakah $userId adalah pembina INTERNAL (staf sekolah) kegiatan ini —
     * dipakai untuk otorisasi "yang mengisi absensi hanya pembina sekolah".
     * Pembina LUAR SEKOLAH tidak punya user_id sama sekali, jadi otomatis
     * tidak pernah lolos cek ini (mereka memang tidak punya akun sistem).
     */
    public function isPembinaInternal(int $userId): bool
    {
        // (int) cast di kedua sisi — jaga-jaga kalau user_id kebaca sebagai
        // string dari database (tergantung driver), supaya perbandingan
        // ketat (===) tidak pernah gagal cocok gara-gara tipe data beda
        // padahal nilainya sama.
        return $this->pembinas->contains(fn ($p) => (int) $p->user_id === $userId);
    }
}
