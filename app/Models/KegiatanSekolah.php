<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Kegiatan sekolah di luar jam KBM (lomba Agustus, tryout & asesmen
 * sumatif, classmeeting, pesantren Ramadan, dsb) yang absensinya diisi
 * oleh WALI KELAS, bukan guru mapel.
 */
class KegiatanSekolah extends Model
{
    use HasFactory;

    protected $table = 'kegiatan_sekolahs';

    public const JENIS = [
        'lomba' => 'Lomba / Perayaan',
        'asesmen' => 'Tryout / Asesmen Sumatif',
        'classmeeting' => 'Classmeeting',
        'keagamaan' => 'Kegiatan Keagamaan',
        'lainnya' => 'Kegiatan Lainnya',
    ];

    public const CAKUPAN = [
        'semua' => 'Semua kelas',
        'tingkat' => 'Satu tingkat',
        'kelas' => 'Kelas tertentu',
    ];

    protected $fillable = [
        'tahun_ajaran_id', 'nama', 'jenis', 'tanggal_mulai', 'tanggal_selesai',
        'hari_aktif', 'cakupan', 'tingkat', 'keterangan', 'kirim_wa_alfa',
        'is_aktif', 'dibuat_oleh_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'hari_aktif' => 'array',
            'kirim_wa_alfa' => 'boolean',
            'is_aktif' => 'boolean',
        ];
    }

    public function tahunAjaran(): BelongsTo { return $this->belongsTo(TahunAjaran::class); }
    public function dibuatOleh(): BelongsTo { return $this->belongsTo(User::class, 'dibuat_oleh_id'); }
    public function absensi(): HasMany { return $this->hasMany(AbsensiKegiatan::class, 'kegiatan_sekolah_id'); }

    /** Kelas yang dipilih manual — hanya dipakai kalau cakupan = 'kelas'. */
    public function kelasTerpilih(): BelongsToMany
    {
        return $this->belongsToMany(Kelas::class, 'kegiatan_kelas', 'kegiatan_sekolah_id', 'kelas_id')->withTimestamps();
    }

    public function jenisLabel(): string
    {
        return self::JENIS[$this->jenis] ?? 'Kegiatan';
    }

    /** Status dihitung dari tanggal, bukan disimpan — jadi tidak pernah basi. */
    public function status(): string
    {
        if (! $this->is_aktif) {
            return 'nonaktif';
        }
        $hariIni = now()->startOfDay();
        if ($hariIni->lt($this->tanggal_mulai->startOfDay())) {
            return 'akan_datang';
        }
        if ($hariIni->gt($this->tanggal_selesai->startOfDay())) {
            return 'selesai';
        }

        return 'berlangsung';
    }

    public function statusLabel(): string
    {
        return match ($this->status()) {
            'nonaktif' => 'Nonaktif',
            'akan_datang' => 'Akan datang',
            'berlangsung' => 'Sedang berlangsung',
            default => 'Selesai',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status()) {
            'nonaktif' => 'bg-slate-100 text-slate-500',
            'akan_datang' => 'bg-sky-50 text-sky-700',
            'berlangsung' => 'bg-emerald-50 text-emerald-700',
            default => 'bg-slate-100 text-slate-600',
        };
    }

    public function rentangLabel(): string
    {
        if ($this->tanggal_mulai->isSameDay($this->tanggal_selesai)) {
            return $this->tanggal_mulai->translatedFormat('d M Y');
        }

        return $this->tanggal_mulai->translatedFormat('d M').' – '.$this->tanggal_selesai->translatedFormat('d M Y');
    }

    /** Apakah kegiatan ini berlangsung pada tanggal tertentu? */
    public function berlangsungPada(string|Carbon $tanggal): bool
    {
        $tgl = $tanggal instanceof Carbon ? $tanggal->copy() : Carbon::parse($tanggal);
        $tgl = $tgl->startOfDay();

        if (! $this->is_aktif) {
            return false;
        }
        if ($tgl->lt($this->tanggal_mulai->startOfDay()) || $tgl->gt($this->tanggal_selesai->startOfDay())) {
            return false;
        }

        // Kalau hari_aktif diisi, kegiatan hanya berlaku pada hari-hari itu
        // (mis. lomba yang hanya digelar tiap Sabtu selama Agustus).
        $hariAktif = $this->hari_aktif ?: [];
        if (! empty($hariAktif)) {
            return in_array(self::namaHari($tgl), $hariAktif, true);
        }

        return true;
    }

    /** Semua tanggal kegiatan ini berlangsung (untuk daftar pilih tanggal). */
    public function daftarTanggal(): Collection
    {
        $hasil = collect();
        $kursor = $this->tanggal_mulai->copy()->startOfDay();
        $akhir = $this->tanggal_selesai->copy()->startOfDay();

        // Batas aman supaya rentang yang salah ketik (mis. sampai tahun
        // depan) tidak membuat halaman berat.
        $batas = 0;
        while ($kursor->lte($akhir) && $batas < 200) {
            if ($this->berlangsungPada($kursor)) {
                $hasil->push($kursor->copy());
            }
            $kursor->addDay();
            $batas++;
        }

        return $hasil;
    }

    /** Kelas yang wajib mengisi absensi kegiatan ini. */
    public function kelasSasaran(): Collection
    {
        if ($this->cakupan === 'kelas') {
            return $this->kelasTerpilih()->orderBy('nama_kelas')->get();
        }

        return Kelas::aktif()
            ->when($this->cakupan === 'tingkat' && $this->tingkat, fn ($q) => $q->where('tingkat', $this->tingkat))
            ->orderBy('nama_kelas')
            ->get();
    }

    public function mencakupKelas(Kelas $kelas): bool
    {
        return $this->kelasSasaran()->contains('id', $kelas->id);
    }

    public function cakupanLabel(): string
    {
        return match ($this->cakupan) {
            'tingkat' => 'Tingkat '.$this->tingkat,
            'kelas' => $this->kelasTerpilih->pluck('nama_kelas')->implode(', ') ?: 'Belum ada kelas dipilih',
            default => 'Semua kelas',
        };
    }

    /**
     * KELAS MANA SAJA YANG SEDANG BERKEGIATAN PADA SUATU TANGGAL.
     *
     * Dipakai pengingat jurnal WhatsApp (App\Console\Commands\
     * KirimPengingatJurnal dan App\Jobs\KirimPengingatJurnalWhatsapp).
     *
     * Alasannya: pada hari kegiatan sekolah, KBM biasa tidak berjalan.
     * Yang mengisi kehadiran adalah WALI KELAS lewat menu Absensi Kegiatan,
     * bukan guru mata pelajaran lewat jurnal mengajar. Menagih guru mapel
     * mengisi jurnal untuk hari itu berarti menagih sesuatu yang memang
     * tidak seharusnya ada — dan itu cara tercepat membuat guru berhenti
     * menganggap serius pengingat ini.
     *
     * Cakupannya dihormati apa adanya: kegiatan yang hanya untuk kelas 7
     * tidak membebaskan kelas 8 dan 9 dari kewajiban mengisi jurnal.
     *
     * Hasilnya di-cache per tanggal selama satu proses berjalan, karena
     * pemanggilnya menelusuri banyak sesi sekaligus dan `kelasSasaran()`
     * melakukan query sendiri untuk tiap kegiatan.
     *
     * @return array<int, int> daftar kelas_id
     */
    public static function kelasIdBerkegiatanPada(string|Carbon $tanggal): array
    {
        $kunci = $tanggal instanceof Carbon ? $tanggal->toDateString() : Carbon::parse($tanggal)->toDateString();

        if (array_key_exists($kunci, static::$cacheKelasBerkegiatan)) {
            return static::$cacheKelasBerkegiatan[$kunci];
        }

        $kelasId = static::berlangsungPadaTanggal($kunci)
            ->flatMap(fn (self $k) => $k->kelasSasaran()->pluck('id'))
            ->unique()
            ->values()
            ->all();

        return static::$cacheKelasBerkegiatan[$kunci] = $kelasId;
    }

    /** Cache per tanggal — lihat kelasIdBerkegiatanPada(). */
    private static array $cacheKelasBerkegiatan = [];

    /** Kosongkan cache; dipakai pengujian dan proses antrian yang berumur panjang. */
    public static function lupakanCacheKegiatan(): void
    {
        static::$cacheKelasBerkegiatan = [];
    }

    /**
     * Nama kegiatan yang mencakup sebuah kelas pada tanggal itu — dipakai
     * untuk menjelaskan kepada Admin mengapa sebuah pengingat dilewati.
     */
    public static function namaKegiatanUntukKelas(string|Carbon $tanggal, int $kelasId): ?string
    {
        $kunci = $tanggal instanceof Carbon ? $tanggal->toDateString() : Carbon::parse($tanggal)->toDateString();

        return static::berlangsungPadaTanggal($kunci)
            ->first(fn (self $k) => $k->kelasSasaran()->contains('id', $kelasId))
            ?->nama;
    }

    public static function namaHari(Carbon $tanggal): string
    {
        $map = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 0 => 'Minggu'];

        return $map[$tanggal->dayOfWeek] ?? 'Senin';
    }

    /**
     * Kegiatan yang berlangsung pada satu tanggal (sudah tersaring is_aktif
     * & hari_aktif). Dipakai oleh dashboard wali kelas dan halaman isi
     * absensi kegiatan.
     */
    public static function berlangsungPadaTanggal(string $tanggal): Collection
    {
        return static::where('is_aktif', true)
            ->whereDate('tanggal_mulai', '<=', $tanggal)
            ->whereDate('tanggal_selesai', '>=', $tanggal)
            ->orderBy('tanggal_mulai')
            ->get()
            ->filter(fn (self $k) => $k->berlangsungPada($tanggal))
            ->values();
    }
}
