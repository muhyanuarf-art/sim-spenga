<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengaturanSekolah extends Model
{
    protected $table = 'pengaturan_sekolahs';

    protected $fillable = [
        'nama_sekolah',
        'pemerintah_daerah',
        'instansi_induk',
        'unit_kerja',
        'kecamatan',
        'alamat_sekolah',
        'website_sekolah',
        'email_sekolah',
        'logo_kiri_path',
        'logo_kanan_path',
        'logo_aplikasi_path',
        'kabupaten_kota',
        'provinsi',
        'nama_kepala_sekolah',
        'nip_kepala_sekolah',
        'format_lokasi_ttd',
    ];

    /** Cache di dalam 1 request, supaya tidak query berkali-kali (dipakai lewat View composer global). */
    protected static ?self $cached = null;

    /**
     * Baris pengaturan tunggal (singleton). Dibuat otomatis dengan nilai
     * default kalau memang belum pernah ada (mis. sebelum migration baru
     * dijalankan ulang / di server lain).
     */
    public static function current(): self
    {
        return static::$cached ??= static::first() ?? static::create([
            'kabupaten_kota' => 'Bumiayu',
            'provinsi' => 'Jawa Tengah',
        ]);
    }

    /** Reset cache — dipanggil setelah update supaya perubahan langsung kepakai di request yang sama. */
    public static function lupakanCache(): void
    {
        static::$cached = null;
    }

    /** Teks lokasi yang dipakai di baris tanda tangan (override kalau diisi, fallback ke Kabupaten/Kota). */
    public function lokasiTtd(): string
    {
        return $this->format_lokasi_ttd ?: ($this->kabupaten_kota ?: '-');
    }

    public function logoKiriUrl(): ?string
    {
        return $this->logo_kiri_path ? asset('storage/' . $this->logo_kiri_path) : null;
    }

    public function logoKananUrl(): ?string
    {
        return $this->logo_kanan_path ? asset('storage/' . $this->logo_kanan_path) : null;
    }

    /**
     * Logo/ikon APLIKASI — tampil di sidebar, halaman login, dan sebagai
     * favicon di tab browser. Berbeda dengan logo kiri/kanan di atas yang
     * KHUSUS untuk KOP Surat dan hanya muncul saat dokumen dicetak.
     */
    public function logoAplikasiUrl(): ?string
    {
        return $this->logo_aplikasi_path ? asset('storage/' . $this->logo_aplikasi_path) : null;
    }

    /**
     * Inisial dua huruf sebagai pengganti logo selama sekolah belum
     * mengunggah apa pun. Diambil dari Nama Sekolah — dulu kotak di sidebar
     * bertuliskan "SP" yang ditulis mati di dalam kode, sehingga sekolah
     * lain yang memakai aplikasi ini tetap melihat inisial yang bukan
     * miliknya.
     */
    public function inisialAplikasi(): string
    {
        $nama = trim((string) $this->nama_sekolah);

        if ($nama === '') {
            return 'SIM';
        }

        $huruf = collect(preg_split('/\s+/', $nama) ?: [])
            ->filter(fn ($kata) => $kata !== '' && ctype_alpha(mb_substr($kata, 0, 1)))
            ->map(fn ($kata) => mb_strtoupper(mb_substr($kata, 0, 1)))
            ->take(2)
            ->implode('');

        return $huruf !== '' ? $huruf : 'SIM';
    }
}
