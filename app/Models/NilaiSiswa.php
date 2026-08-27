<?php

namespace App\Models;

use App\Support\SkemaPenilaian;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nilai satu siswa untuk SATU mata pelajaran pada SATU periode
 * (tahun ajaran + semester).
 *
 * Nilai MENTAH (yang diketik guru) ada di kolom JSON `formatif` &
 * `sumatif_lm` plus kolom `asts` & `asas`. Nilai HASIL HITUNGAN
 * (rata_formatif, rata_sumatif_lm, nilai_akhir, predikat, tuntas, lengkap)
 * ikut disimpan, dan SELALU diisi ulang lewat hitungUlang() setiap kali
 * nilai mentahnya berubah — jadi tidak mungkin ada nilai akhir yang
 * "ketinggalan" dari nilai mentahnya.
 *
 * Rumusnya sendiri TIDAK ditulis di sini, melainkan di
 * App\Support\SkemaPenilaian (satu-satunya tempat rumus dihitung).
 */
class NilaiSiswa extends Model
{
    protected $table = 'nilai_siswas';

    protected $fillable = [
        'siswa_id', 'mata_pelajaran_id', 'tahun_ajaran_id', 'kelas_id',
        'formatif', 'sumatif_lm', 'asts', 'asas',
        'rata_formatif', 'rata_sumatif_lm', 'nilai_akhir', 'predikat', 'tuntas', 'lengkap',
        'diperbarui_oleh_id',
    ];

    protected function casts(): array
    {
        return [
            'formatif' => 'array',
            'sumatif_lm' => 'array',
            'asts' => 'float',
            'asas' => 'float',
            'rata_formatif' => 'float',
            'rata_sumatif_lm' => 'float',
            'nilai_akhir' => 'float',
            'tuntas' => 'boolean',
            'lengkap' => 'boolean',
        ];
    }

    public function siswa(): BelongsTo { return $this->belongsTo(Siswa::class, 'siswa_id'); }
    public function mapel(): BelongsTo { return $this->belongsTo(MataPelajaran::class, 'mata_pelajaran_id'); }
    public function kelas(): BelongsTo { return $this->belongsTo(Kelas::class, 'kelas_id'); }
    public function tahunAjaran(): BelongsTo { return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id'); }
    public function diperbaruiOleh(): BelongsTo { return $this->belongsTo(User::class, 'diperbarui_oleh_id'); }

    /**
     * Hitung ulang seluruh kolom turunan dari nilai mentah yang sedang
     * menempel di model ini. TIDAK menyimpan — pemanggil yang memutuskan
     * kapan save() (supaya bisa disatukan dalam 1 transaksi saat menyimpan
     * satu kelas penuh).
     */
    public function hitungUlang(SkemaPenilaian $skema): self
    {
        $hasil = $skema->hitung(
            $this->formatif ?? [],
            $this->sumatif_lm ?? [],
            $this->asts,
            $this->asas,
        );

        $this->rata_formatif = $hasil['rata_formatif'];
        $this->rata_sumatif_lm = $hasil['rata_sumatif_lm'];
        $this->nilai_akhir = $hasil['nilai_akhir'];
        $this->predikat = $hasil['predikat'];
        $this->tuntas = $hasil['tuntas'];
        $this->lengkap = $hasil['lengkap'];

        return $this;
    }

    /** Nilai TPF ke-$nomor (null kalau belum diisi). */
    public function tpf(int $nomor): ?float
    {
        $nilai = ($this->formatif ?? [])[$nomor] ?? ($this->formatif ?? [])[(string) $nomor] ?? null;

        return is_numeric($nilai) ? (float) $nilai : null;
    }

    /** Nilai SUM atau REM lingkup materi ke-$nomor. $jenis: 'sum' | 'rem'. */
    public function lm(int $nomor, string $jenis): ?float
    {
        $baris = ($this->sumatif_lm ?? [])[$nomor] ?? ($this->sumatif_lm ?? [])[(string) $nomor] ?? null;
        $nilai = is_array($baris) ? ($baris[$jenis] ?? null) : null;

        return is_numeric($nilai) ? (float) $nilai : null;
    }

    /**
     * Apakah lingkup materi ke-$nomor WAJIB diisi remedinya — yaitu nilai
     * SUM-nya sudah ada tapi masih di bawah KKTP. Dipakai form daftar
     * nilai untuk menyorot kolom REM yang belum diisi.
     */
    public function wajibRemedi(int $nomor, SkemaPenilaian $skema): bool
    {
        $sum = $this->lm($nomor, 'sum');

        return $sum !== null && $sum < $skema->kktpMin;
    }

    /** Nilai akhir dibulatkan seperti yang ditulis di rapor (bilangan bulat). */
    public function nilaiRapor(): ?int
    {
        return $this->nilai_akhir === null ? null : (int) round($this->nilai_akhir);
    }

    /** Warna badge untuk nilai akhir, seragam di semua halaman. */
    public function warnaPredikat(): string
    {
        return match ($this->predikat) {
            'A' => 'bg-emerald-50 text-emerald-700',
            'B' => 'bg-sky-50 text-sky-700',
            'C' => 'bg-amber-50 text-amber-700',
            'D' => 'bg-rose-50 text-rose-700',
            default => 'bg-slate-100 text-slate-500',
        };
    }
}
