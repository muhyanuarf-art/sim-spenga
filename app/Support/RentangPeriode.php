<?php

namespace App\Support;

use App\Models\TahunAjaran;
use Illuminate\Support\Carbon;

/**
 * SATU SUMBER KEBENARAN: "tanggal berapa saja yang termasuk periode ini?"
 *
 * Dipakai dua hal yang harus selalu sepakat:
 *   1. LAPORAN — Laporan Akhir Semester menyaring data memakai rentang ini.
 *   2. VALIDASI — App\Rules\DalamPeriode menolak tanggal di luar rentang ini.
 *
 * Kalau keduanya memakai perhitungan sendiri-sendiri, bisa terjadi keadaan
 * paling membingungkan: data lolos disimpan tapi tidak pernah muncul di
 * laporan periodenya. Karena itu keduanya memanggil kelas ini.
 *
 * DARI MANA TANGGALNYA
 * ====================
 * Kolom `tanggal_mulai` & `tanggal_selesai` pada tahun_ajarans BOLEH kosong
 * — dulu isiannya sengaja dihilangkan dari form Tahun Ajaran supaya lebih
 * ringkas, dan periode yang dibuat sejak itu bernilai NULL. Jadi rentangnya
 * dicari bertahap:
 *
 *   1. Pakai tanggal yang tersimpan, kalau keduanya terisi.
 *   2. Kalau kosong, TURUNKAN dari nama & semester dengan kalender sekolah
 *      Indonesia pada umumnya:
 *         Ganjil "2026/2027" → 1 Juli 2026  s.d. 31 Desember 2026
 *         Genap  "2026/2027" → 1 Januari 2027 s.d. 30 Juni 2027
 *      Rentang turunan ini sengaja DILEBARKAN sedikit (lihat KELONGGARAN)
 *      supaya sekolah yang semesternya bergeser beberapa hari tidak
 *      tertolak — validasi ini untuk mencegah salah ketik tahun/semester,
 *      bukan untuk mengatur kalender akademik secara kaku.
 *   3. Kalau `nama` tidak berpola "YYYY/YYYY", rentangnya dianggap tidak
 *      diketahui (null) dan validasi apa pun dilewati — lebih baik tidak
 *      memblokir daripada memblokir berdasarkan tebakan.
 */
class RentangPeriode
{
    /**
     * Kelonggaran hari untuk rentang TURUNAN (bukan yang diisi admin).
     * Kalender sekolah tidak selalu mulai tepat 1 Juli atau berakhir tepat
     * 30 Juni; 21 hari cukup menampung pergeseran wajar tanpa membuat
     * validasinya jadi tak berguna.
     */
    private const KELONGGARAN_HARI = 21;

    /** Cache per periode dalam satu request. */
    private static array $cache = [];

    /**
     * Rentang tanggal milik sebuah periode.
     *
     * @return array{0: Carbon, 1: Carbon, 2: bool}|null
     *         [mulai, selesai, diturunkan] — null bila tidak bisa ditentukan.
     */
    public static function untuk(TahunAjaran $periode): ?array
    {
        if (array_key_exists($periode->id, self::$cache)) {
            return self::$cache[$periode->id];
        }

        // 1. Tanggal yang benar-benar diisi admin dipakai apa adanya,
        //    tanpa kelonggaran — kalau sudah ditetapkan, itu yang berlaku.
        if ($periode->tanggal_mulai && $periode->tanggal_selesai) {
            return self::$cache[$periode->id] = [
                $periode->tanggal_mulai->copy()->startOfDay(),
                $periode->tanggal_selesai->copy()->endOfDay(),
                false,
            ];
        }

        // 2. Diturunkan dari nama + semester.
        if (! preg_match('/^(\d{4})\/(\d{4})$/', (string) $periode->nama, $m)) {
            return self::$cache[$periode->id] = null;
        }

        [$mulai, $selesai] = $periode->semester === 'Genap'
            ? [Carbon::create((int) $m[2], 1, 1), Carbon::create((int) $m[2], 6, 30)]
            : [Carbon::create((int) $m[1], 7, 1), Carbon::create((int) $m[1], 12, 31)];

        return self::$cache[$periode->id] = [
            $mulai->subDays(self::KELONGGARAN_HARI)->startOfDay(),
            $selesai->addDays(self::KELONGGARAN_HARI)->endOfDay(),
            true,
        ];
    }

    /** Apakah $tanggal termasuk dalam periode ini? true juga bila rentangnya tak diketahui. */
    public static function memuat(TahunAjaran $periode, string|Carbon|null $tanggal): bool
    {
        if ($tanggal === null || $tanggal === '') {
            return true;
        }

        $rentang = self::untuk($periode);
        if ($rentang === null) {
            return true; // rentang tidak diketahui — jangan memblokir
        }

        try {
            $cek = $tanggal instanceof Carbon ? $tanggal->copy() : Carbon::parse($tanggal);
        } catch (\Throwable) {
            return true; // formatnya salah — biar aturan 'date' yang menangani
        }

        return $cek->betweenIncluded($rentang[0], $rentang[1]);
    }

    /** Teks rentang untuk pesan kesalahan, mis. "10 Juni 2026 sampai 21 Januari 2027". */
    public static function label(TahunAjaran $periode): ?string
    {
        $rentang = self::untuk($periode);

        if ($rentang === null) {
            return null;
        }

        return $rentang[0]->translatedFormat('d F Y').' sampai '.$rentang[1]->translatedFormat('d F Y');
    }

    /** Buang cache — dipakai pengujian & setelah tanggal periode diubah. */
    public static function lupakanCache(): void
    {
        self::$cache = [];
    }
}
