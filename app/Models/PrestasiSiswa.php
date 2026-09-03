<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu prestasi yang diraih seorang siswa.
 *
 * Lihat migrasi create_prestasi_siswas_table untuk alasan lengkap
 * mengapa tabel ini punya DUA penulis (wali kelas & kesiswaan) dan
 * mengapa ada penanda verifikasi.
 */
class PrestasiSiswa extends Model
{
    use HasFactory;

    protected $fillable = [
        'siswa_id', 'tahun_ajaran_id', 'nama', 'bidang', 'tingkat', 'peringkat',
        'penyelenggara', 'tanggal', 'keterangan', 'sertifikat_path',
        'dicatat_oleh', 'diverifikasi_at', 'diverifikasi_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'diverifikasi_at' => 'datetime',
        ];
    }

    /**
     * Label yang dibaca manusia. Disimpan di sini — bukan ditulis ulang di
     * tiap view — supaya daftar pilihan di formulir, tampilan tabel,
     * portal orang tua, dan rekap tidak pernah berbeda kata.
     */
    public const BIDANG = [
        'akademik' => 'Akademik',
        'non_akademik' => 'Non-Akademik',
    ];

    public const TINGKAT = [
        'sekolah' => 'Sekolah',
        'kecamatan' => 'Kecamatan',
        'kabupaten' => 'Kabupaten/Kota',
        'provinsi' => 'Provinsi',
        'nasional' => 'Nasional',
        'internasional' => 'Internasional',
    ];

    public const PERINGKAT = [
        'juara_1' => 'Juara 1',
        'juara_2' => 'Juara 2',
        'juara_3' => 'Juara 3',
        'harapan' => 'Juara Harapan',
        'finalis' => 'Finalis',
        'peserta' => 'Peserta',
    ];

    /**
     * Bobot tingkat, dipakai mengurutkan "prestasi terbaik" lebih dulu di
     * rekap. Angka besar = lebih tinggi.
     */
    public const BOBOT_TINGKAT = [
        'internasional' => 6, 'nasional' => 5, 'provinsi' => 4,
        'kabupaten' => 3, 'kecamatan' => 2, 'sekolah' => 1,
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function sudahDiverifikasi(): bool
    {
        return $this->diverifikasi_at !== null;
    }

    public function labelBidang(): string
    {
        return self::BIDANG[$this->bidang] ?? $this->bidang;
    }

    public function labelTingkat(): string
    {
        return self::TINGKAT[$this->tingkat] ?? $this->tingkat;
    }

    public function labelPeringkat(): string
    {
        return self::PERINGKAT[$this->peringkat] ?? $this->peringkat;
    }

    /** Ringkasan satu baris: "Juara 1 · Kabupaten/Kota". */
    public function ringkas(): string
    {
        return $this->labelPeringkat().' · '.$this->labelTingkat();
    }

    /** Hanya yang sudah dipastikan benar oleh kesiswaan. */
    public function scopeTerverifikasi(Builder $query): Builder
    {
        return $query->whereNotNull('diverifikasi_at');
    }

    public function scopeBelumDiverifikasi(Builder $query): Builder
    {
        return $query->whereNull('diverifikasi_at');
    }

    /**
     * Urutan baku: yang terbaru dulu, dan pada tanggal yang sama tingkat
     * tertinggi lebih dulu — supaya prestasi paling berarti tidak
     * tenggelam di antara yang lain.
     */
    public function scopeUrutanBaku(Builder $query): Builder
    {
        return $query
            ->orderByDesc('tanggal')
            ->orderByRaw(
                "FIELD(tingkat, 'sekolah','kecamatan','kabupaten','provinsi','nasional','internasional') DESC"
            )
            ->orderBy('peringkat');
    }
}
