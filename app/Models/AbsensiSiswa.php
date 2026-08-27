<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsensiSiswa extends Model
{
    use HasFactory;

    protected $table = 'absensi_siswas';

    /**
     * Relasi yang perlu di-eager-load kalau hasilnya akan dipakai untuk
     * finalPerHari()/alfaDariRecordsPerSiswa(). Dikumpulkan di satu tempat
     * supaya semua pemanggil (dashboard, rekap kelas, portal orang tua,
     * notifikasi WA) tidak mudah tertinggal saat daftarnya bertambah —
     * dulu daftar ini disalin manual di 6 tempat berbeda.
     */
    public const RELASI_KONTEKS = [
        'jurnal.mapel', 'jurnal.jamPelajaran', 'jurnal.jamPelajaranAkhir',
        'absensiKegiatan.kegiatan',
    ];

    /**
     * Prioritas absensi kegiatan sekolah saat menentukan status FINAL
     * seorang siswa dalam sehari. Sengaja jauh di atas nomor jam pelajaran
     * mana pun: kegiatan seperti lomba, asesmen, classmeeting, atau
     * pesantren Ramadan menggantikan jam KBM hari itu, dan yang mengisinya
     * adalah WALI KELAS — sumber paling berwenang untuk kehadiran siswa.
     */
    private const PRIORITAS_KEGIATAN = 900;

    protected $fillable = [
        'jurnal_mengajar_id', 'absensi_kegiatan_id', 'sumber',
        'siswa_id', 'kelas_id', 'tanggal', 'status', 'keterangan',
    ];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function jurnal(): BelongsTo { return $this->belongsTo(JurnalMengajar::class, 'jurnal_mengajar_id'); }
    public function absensiKegiatan(): BelongsTo { return $this->belongsTo(AbsensiKegiatan::class, 'absensi_kegiatan_id'); }
    public function siswa(): BelongsTo { return $this->belongsTo(Siswa::class, 'siswa_id'); }
    public function kelas(): BelongsTo { return $this->belongsTo(Kelas::class, 'kelas_id'); }

    public function dariKegiatan(): bool
    {
        return $this->absensi_kegiatan_id !== null;
    }

    /** Kegiatan sekolah asal baris ini (null kalau berasal dari KBM). */
    public function kegiatan(): ?KegiatanSekolah
    {
        return $this->absensiKegiatan?->kegiatan;
    }

    /**
     * Keterangan singkat "absensi ini dari mana": nama mata pelajaran untuk
     * KBM, atau nama kegiatan untuk hari kegiatan sekolah.
     */
    public function konteksLabel(): ?string
    {
        if ($this->dariKegiatan()) {
            return $this->kegiatan()?->nama;
        }

        return $this->jurnal?->mapel?->nama_mapel;
    }

    /**
     * STEP 2 Bagian 8 — tidak ada tahun_ajaran_id di absensi_siswas.
     * Untuk KBM periodenya diketahui lewat jurnal -> jadwal; untuk kegiatan
     * sekolah, langsung dari kegiatannya.
     */
    public function periode(): ?TahunAjaran
    {
        if ($this->dariKegiatan()) {
            return $this->kegiatan()?->tahunAjaran;
        }

        return $this->jurnal?->periode();
    }

    /**
     * Aturan "Absensi Kelas": kalau 1 siswa tercatat absen lebih dari sekali
     * pada tanggal yang sama, status yang dipakai untuk keperluan KELAS /
     * WALI KELAS adalah:
     *
     * 1. Absensi KEGIATAN SEKOLAH (diisi wali kelas) kalau ada — karena
     *    kegiatan menggantikan jam KBM hari itu.
     * 2. Kalau tidak ada, status dari GURU MAPEL DENGAN JAM PALING AKHIR
     *    hari itu (mis. Hadir menurut guru jam ke-1 tapi Alfa menurut guru
     *    jam ke-2 karena membolos di tengah hari).
     *
     * Ini TIDAK memengaruhi laporan per guru mapel (LaporanGuruController),
     * yang tetap menampilkan absensi versi guru tsb apa adanya.
     *
     * @param  \Illuminate\Support\Collection<int, AbsensiSiswa>  $records  Sebaiknya sudah eager-load AbsensiSiswa::RELASI_KONTEKS.
     * @return \Illuminate\Support\Collection<string, AbsensiSiswa>  Dikelompokkan per tanggal (Y-m-d), 1 record final per tanggal.
     */
    public static function finalPerHari($records)
    {
        return $records
            ->groupBy(fn ($r) => $r->tanggal->format('Y-m-d'))
            ->map(fn ($recordsHari) => $recordsHari->sortByDesc(fn ($r) => static::prioritas($r))->first());
    }

    /** Urutan "siapa yang menentukan status hari itu" — makin besar makin menentukan. */
    private static function prioritas(self $record): int
    {
        if ($record->dariKegiatan()) {
            return self::PRIORITAS_KEGIATAN;
        }

        $jurnal = $record->jurnal;
        $jamAkhir = $jurnal?->jamPelajaranAkhir ?? $jurnal?->jamPelajaran;

        return (int) ($jamAkhir?->jam_ke ?? 0);
    }

    /**
     * Daftar siswa yang status Absensi Kelas-nya hari ini = Alfa (lihat
     * finalPerHari()). Kalau $kelasId diisi, hanya untuk 1 kelas itu.
     *
     * @return \Illuminate\Support\Collection<int, array{siswa: mixed, kelas: mixed, mapel: ?string, jam_ke: ?int, kegiatan: ?string}>
     */
    public static function siswaAlfaHariIni(?int $kelasId = null)
    {
        return static::siswaAlfaHariIniByTanggal($kelasId, now()->toDateString());
    }

    /** Sama seperti siswaAlfaHariIni(), tapi untuk tanggal mana pun. */
    public static function siswaAlfaHariIniByTanggal(?int $kelasId, string $tanggal)
    {
        $query = static::whereDate('tanggal', $tanggal)
            ->with(array_merge(['siswa', 'kelas'], static::RELASI_KONTEKS));

        if ($kelasId) {
            $query->where('kelas_id', $kelasId);
        }

        return static::alfaDariRecordsPerSiswa($query->get()->groupBy('siswa_id'));
    }

    /**
     * PERBAIKAN PERFORMA — dipakai dari data yang SUDAH di-fetch sebelumnya
     * (mis. DashboardController yang sudah mengambil seluruh absensi hari
     * ini untuk rekap status), supaya tidak query ulang dari nol.
     *
     * @param  \Illuminate\Support\Collection  $recordsPerSiswaId  Hasil groupBy('siswa_id'), sudah eager-load 'siswa', 'kelas', dan RELASI_KONTEKS.
     */
    public static function alfaDariRecordsPerSiswa($recordsPerSiswaId)
    {
        return $recordsPerSiswaId
            ->map(fn ($recordsSiswa) => static::finalPerHari($recordsSiswa)->first())
            ->filter(fn ($r) => $r && $r->status === 'Alfa')
            ->map(fn ($r) => [
                'siswa' => $r->siswa,
                'kelas' => $r->kelas,
                // Untuk hari kegiatan, kolom "Menurut Mapel" diisi nama
                // kegiatannya supaya jelas Alfa-nya dalam rangka apa.
                'mapel' => $r->konteksLabel(),
                'jam_ke' => $r->dariKegiatan()
                    ? null
                    : ($r->jurnal?->jamPelajaranAkhir?->jam_ke ?? $r->jurnal?->jamPelajaran?->jam_ke),
                'kegiatan' => $r->dariKegiatan() ? $r->kegiatan()?->nama : null,
            ])
            ->sortBy(fn ($x) => optional($x['kelas'])->nama_kelas)
            ->values();
    }
}
