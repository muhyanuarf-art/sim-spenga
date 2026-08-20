<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JurnalMengajar extends Model
{
    use HasFactory;

    protected $fillable = [
        'jadwal_pelajaran_id', 'guru_id', 'kelas_id', 'mata_pelajaran_id',
        'jam_pelajaran_id', 'jam_pelajaran_id_akhir',
        'tanggal', 'materi', 'kegiatan', 'jumlah_hadir', 'jumlah_sakit', 'jumlah_izin', 'jumlah_alfa', 'keterangan',
    ];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function jadwal(): BelongsTo { return $this->belongsTo(JadwalPelajaran::class, 'jadwal_pelajaran_id'); }
    public function guru(): BelongsTo { return $this->belongsTo(User::class, 'guru_id'); }
    public function kelas(): BelongsTo { return $this->belongsTo(Kelas::class, 'kelas_id'); }
    public function mapel(): BelongsTo { return $this->belongsTo(MataPelajaran::class, 'mata_pelajaran_id'); }
    public function jamPelajaran(): BelongsTo { return $this->belongsTo(JamPelajaran::class, 'jam_pelajaran_id'); }
    public function jamPelajaranAkhir(): BelongsTo { return $this->belongsTo(JamPelajaran::class, 'jam_pelajaran_id_akhir'); }
    public function absensi(): HasMany { return $this->hasMany(AbsensiSiswa::class, 'jurnal_mengajar_id'); }
    public function slots(): HasMany { return $this->hasMany(JurnalMengajarSlot::class, 'jurnal_mengajar_id'); }

    /**
     * STEP 2 Bagian 8 — jurnal_mengajars TIDAK punya tahun_ajaran_id
     * sendiri (sengaja, sesuai instruksi "jangan tambah kolom periode
     * membabi buta kalau sudah bisa diketahui lewat relasi"). Periodenya
     * diketahui lewat jadwal_pelajaran_id -> jadwal_pelajarans.tahun_ajaran_id.
     * Dipakai App\Support\PeriodeAkademik::pastikanTidakTerkunci().
     * (Bukan relasi Eloquent — cuma pass-through 1 hop lewat relasi jadwal().)
     */
    public function periode(): ?TahunAjaran
    {
        return $this->jadwal?->tahunAjaran;
    }

    /**
     * Label rentang jam untuk 1 sesi mengajar, mis. "Jam ke-1 - Jam ke-3
     * (07:00 - 09:15)" kalau beberapa jam, atau label jam tunggal kalau cuma 1 jam.
     */
    public function getLabelSesiAttribute(): string
    {
        $awal = $this->jamPelajaran;
        $akhir = $this->jamPelajaranAkhir;

        if (!$awal) {
            return '-';
        }
        if (!$akhir || $akhir->id === $awal->id) {
            return $awal->label;
        }

        $mulai = substr($awal->jam_mulai, 0, 5);
        $selesai = substr($akhir->jam_selesai, 0, 5);
        return "Jam ke-{$awal->jam_ke} s.d ke-{$akhir->jam_ke} ({$mulai} - {$selesai})";
    }

    public function getJumlahJamAttribute(): int
    {
        $awal = $this->jamPelajaran;
        $akhir = $this->jamPelajaranAkhir;
        if (!$awal) {
            return 0;
        }
        return $akhir ? max(1, $akhir->jam_ke - $awal->jam_ke + 1) : 1;
    }
}
