<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu rentang tanggal yang tidak ada KBM.
 *
 * Dipakai di tiga tempat, dan ketiganya menanyakan hal yang sama —
 * "tanggal ini libur atau tidak":
 *
 *   KirimPengintJurnal          menahan pengingat sebelum barisnya dibuat
 *   KirimPengingatJurnalWhatsapp menahan baris yang terlanjur mengantre
 *   RekapController              tidak menghitungnya sebagai jurnal bolong
 */
class HariLibur extends Model
{
    protected $fillable = [
        'tanggal_mulai',
        'tanggal_selesai',
        'keterangan',
        'dibuat_oleh',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    /**
     * Cache per-request.
     *
     * Penjadwal memanggil pemeriksaan ini untuk puluhan sesi sekaligus,
     * dan Rekapitulasi Kepatuhan untuk tiap tanggal dalam sebulan. Tanpa
     * cache, satu halaman rekap bisa menembak puluhan query yang
     * jawabannya sama persis.
     *
     * @var array<string, self|null>
     */
    private static array $cache = [];

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /** Libur yang mencakup tanggal ini, atau null bila hari itu masuk. */
    public static function pada(Carbon|string $tanggal): ?self
    {
        $kunci = $tanggal instanceof Carbon
            ? $tanggal->toDateString()
            : Carbon::parse($tanggal)->toDateString();

        if (array_key_exists($kunci, self::$cache)) {
            return self::$cache[$kunci];
        }

        return self::$cache[$kunci] = self::query()
            ->whereDate('tanggal_mulai', '<=', $kunci)
            ->whereDate('tanggal_selesai', '>=', $kunci)
            ->orderBy('tanggal_mulai')
            ->first();
    }

    public static function adalahLibur(Carbon|string $tanggal): bool
    {
        return self::pada($tanggal) !== null;
    }

    /**
     * Tanggal-tanggal libur di dalam satu rentang, sebagai "Y-m-d".
     *
     * Satu query untuk sebulan penuh, dipakai Rekapitulasi Kepatuhan.
     * Memanggil pada() tiga puluh kali juga benar, tetapi ini sekali.
     *
     * @return array<int, string>
     */
    public static function tanggalDalam(Carbon $awal, Carbon $akhir): array
    {
        $libur = [];

        $baris = self::query()
            ->whereDate('tanggal_mulai', '<=', $akhir->toDateString())
            ->whereDate('tanggal_selesai', '>=', $awal->toDateString())
            ->get();

        foreach ($baris as $b) {
            // Rentangnya dipotong pada batas yang diminta, supaya libur
            // panjang yang melewati pergantian bulan tidak menghasilkan
            // tanggal di luar bulan yang sedang dilihat.
            $mulai = $b->tanggal_mulai->greaterThan($awal) ? $b->tanggal_mulai->copy() : $awal->copy();
            $selesai = $b->tanggal_selesai->lessThan($akhir) ? $b->tanggal_selesai->copy() : $akhir->copy();

            for ($t = $mulai; $t->lte($selesai); $t->addDay()) {
                $libur[$t->toDateString()] = true;
            }
        }

        return array_keys($libur);
    }

    /** Berapa hari liburnya, dihitung inklusif. Sehari = 1, bukan 0. */
    public function jumlahHari(): int
    {
        return $this->tanggal_mulai->diffInDays($this->tanggal_selesai) + 1;
    }

    public function sehari(): bool
    {
        return $this->tanggal_mulai->isSameDay($this->tanggal_selesai);
    }

    public function label(): string
    {
        if ($this->sehari()) {
            return $this->tanggal_mulai->translatedFormat('l, d F Y');
        }

        return $this->tanggal_mulai->translatedFormat('d F Y')
            .' sampai '
            .$this->tanggal_selesai->translatedFormat('d F Y');
    }

    public function sudahLewat(): bool
    {
        return $this->tanggal_selesai->isBefore(Carbon::today());
    }

    public function sedangBerlangsung(): bool
    {
        return ! $this->sudahLewat()
            && $this->tanggal_mulai->lte(Carbon::today());
    }
}
