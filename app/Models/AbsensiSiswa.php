<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsensiSiswa extends Model
{
    use HasFactory;

    protected $table = 'absensi_siswas';

    protected $fillable = [
        'jurnal_mengajar_id', 'siswa_id', 'kelas_id', 'tanggal', 'status', 'keterangan',
    ];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function jurnal(): BelongsTo { return $this->belongsTo(JurnalMengajar::class, 'jurnal_mengajar_id'); }
    public function siswa(): BelongsTo { return $this->belongsTo(Siswa::class, 'siswa_id'); }
    public function kelas(): BelongsTo { return $this->belongsTo(Kelas::class, 'kelas_id'); }

    /**
     * Aturan "Absensi Kelas": kalau 1 siswa tercatat absen oleh lebih dari 1
     * guru mapel pada tanggal yang sama (mis. Hadir menurut guru jam ke-1,
     * tapi Alfa menurut guru jam ke-2 karena membolos di tengah hari), maka
     * status yang dipakai untuk keperluan KELAS/WALI KELAS adalah status
     * dari GURU MAPEL DENGAN JAM PALING AKHIR pada hari itu — bukan
     * digabung/ditimpa asal urutan data, dan tidak dihitung dobel per mapel.
     *
     * Ini TIDAK memengaruhi laporan per guru mapel (LaporanGuruController),
     * yang tetap menampilkan absensi versi guru tsb apa adanya.
     *
     * @param  \Illuminate\Support\Collection<int, AbsensiSiswa>  $records  Harus sudah eager-load 'jurnal.jamPelajaran' & 'jurnal.jamPelajaranAkhir'.
     * @return \Illuminate\Support\Collection<string, AbsensiSiswa>  Dikelompokkan per tanggal (Y-m-d), 1 record final per tanggal.
     */
    public static function finalPerHari($records)
    {
        return $records
            ->groupBy(fn ($r) => $r->tanggal->format('Y-m-d'))
            ->map(function ($recordsHari) {
                return $recordsHari->sortByDesc(function ($r) {
                    $jurnal = $r->jurnal;
                    $jamAkhir = $jurnal?->jamPelajaranAkhir ?? $jurnal?->jamPelajaran;
                    return $jamAkhir?->jam_ke ?? 0;
                })->first();
            });
    }

    /**
     * Daftar siswa yang status Absensi Kelas-nya hari ini = Alfa, yaitu
     * status dari guru mapel dengan jam paling akhir yang sudah mengisi
     * absensi hari ini (lihat finalPerHari()). Kalau $kelasId diisi, hanya
     * untuk 1 kelas itu; kalau null, seluruh sekolah.
     *
     * @return \Illuminate\Support\Collection<int, array{siswa: mixed, kelas: mixed, mapel: ?string, jam_ke: ?int}>
     */
    public static function siswaAlfaHariIni(?int $kelasId = null)
    {
        return static::siswaAlfaHariIniByTanggal($kelasId, now()->toDateString());
    }

    /**
     * Sama seperti siswaAlfaHariIni(), tapi untuk tanggal manapun (bukan
     * hanya hari ini). Dipakai DashboardController untuk widget "Siswa
     * Alfa Hari Ini" di halaman utama tiap role.
     */
    public static function siswaAlfaHariIniByTanggal(?int $kelasId, string $tanggal)
    {
        $query = static::whereDate('tanggal', $tanggal)
            ->with(['siswa', 'kelas', 'jurnal.mapel', 'jurnal.jamPelajaran', 'jurnal.jamPelajaranAkhir']);

        if ($kelasId) {
            $query->where('kelas_id', $kelasId);
        }

        return $query->get()
            ->groupBy('siswa_id')
            ->map(fn ($recordsSiswa) => static::finalPerHari($recordsSiswa)->first())
            ->filter(fn ($r) => $r && $r->status === 'Alfa')
            ->map(fn ($r) => [
                'siswa' => $r->siswa,
                'kelas' => $r->kelas,
                'mapel' => $r->jurnal?->mapel?->nama_mapel,
                'jam_ke' => $r->jurnal?->jamPelajaranAkhir?->jam_ke ?? $r->jurnal?->jamPelajaran?->jam_ke,
            ])
            ->sortBy(fn ($x) => optional($x['kelas'])->nama_kelas)
            ->values();
    }
}
