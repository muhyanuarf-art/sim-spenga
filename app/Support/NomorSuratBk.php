<?php

namespace App\Support;

use App\Models\Surat;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Format nomor surat BK — TIDAK auto-increment seperti App\Support\
 * NomorSurat (yang dipakai fitur surat umum sebelumnya). Sesuai
 * instruksi: "422" & "BK" tetap/otomatis, bulan-romawi & tahun otomatis
 * dari tanggal surat, TAPI nomor urutnya diisi TANGAN oleh guru BK
 * (wajib, tidak dihasilkan sistem) — supaya cocok dengan buku agenda
 * surat fisik yang sudah mereka pakai.
 *
 * Contoh hasil: 422/15/BK/VIII/2026
 */
class NomorSuratBk
{
    private const BULAN_ROMAWI = [
        1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
        7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
    ];

    public const PREFIX_TETAP = '422';
    public const KODE_TETAP = 'BK';

    public static function buat(string $nomorUrutManual, string $tanggal): string
    {
        $tanggalObj = Carbon::parse($tanggal);
        $bulanRomawi = self::BULAN_ROMAWI[(int) $tanggalObj->month];

        return sprintf(
            '%s/%s/%s/%s/%d',
            self::PREFIX_TETAP,
            trim($nomorUrutManual),
            self::KODE_TETAP,
            $bulanRomawi,
            $tanggalObj->year
        );
    }

    /** Pratinjau format, dipakai di form sebelum nomor urut diisi. */
    public static function pratinjau(string $tanggal): string
    {
        return self::buat('...', $tanggal);
    }

    /**
     * KUNCI PEMBANDING nomor surat — dipakai untuk memastikan tidak ada
     * DUA SURAT dengan nomor yang sama (kolom `surats.nomor_kunci`, yang
     * diberi unique index).
     *
     * Kenapa tidak membandingkan `nomor_surat` mentah-mentah: guru BK
     * menulis nomor urut dengan tangan, dan "001", "01", "1", serta " 1 "
     * itu SATU nomor yang sama di buku agenda surat — tapi sebagai teks
     * ketiganya berbeda, sehingga perbandingan apa adanya akan meloloskan
     * nomor kembar. Contoh nyata yang ditemukan di data:
     * "422/001/BK/VIII/2026" dan "422/1/BK/VIII/2026" adalah nomor 1 yang
     * sama persis.
     *
     * Maka tiap ruas nomor dinormalkan dulu: nol di depan dibuang untuk
     * ruas yang seluruhnya angka, spasi dirapikan, huruf disamakan jadi
     * kapital. Ruas non-angka (mis. "BK", "VIII", atau nomor bergaya
     * "15A") tetap dipertahankan apa adanya.
     *
     *   "422/001/BK/VIII/2026" → "422/1/BK/VIII/2026"
     *   "422/1/BK/VIII/2026"   → "422/1/BK/VIII/2026"   (kembar, tertangkap)
     *   "001/SP/VIII/2026"     → "1/SP/VIII/2026"       (format lama, tetap beda)
     *
     * Sengaja menormalkan SELURUH string nomor surat (bukan menghitung
     * ulang dari nomor_urut + tanggal), supaya surat berformat lama yang
     * seri nomornya berbeda tidak ikut dianggap kembar dengan surat BK.
     *
     * Yang disimpan & dicetak tetap nomor apa adanya yang diketik guru —
     * kunci ini hanya dipakai di balik layar untuk pembandingan.
     */
    public static function kunci(?string $nomorSurat): ?string
    {
        $nomorSurat = trim((string) $nomorSurat);

        if ($nomorSurat === '') {
            return null;
        }

        $ruas = array_map(static function (string $bagian): string {
            $bagian = strtoupper(trim($bagian));

            // Hanya ruas yang seluruhnya angka yang dibuang nol depannya.
            // ltrim() bisa menghabiskan "000" jadi string kosong — kembalikan "0".
            if ($bagian !== '' && ctype_digit($bagian)) {
                return ltrim($bagian, '0') ?: '0';
            }

            return $bagian;
        }, explode('/', $nomorSurat));

        return implode('/', $ruas);
    }

    /**
     * Tolak nomor surat yang sudah dipakai surat lain.
     *
     * Nomor surat adalah identitas surat di buku agenda — dua surat dengan
     * nomor sama membuat arsip tidak bisa ditelusuri. Diletakkan di sini
     * (bukan di salah satu controller) karena surat BK bisa dibuat dari DUA
     * pintu: menu Surat (SuratController) dan menu Pemanggilan Orang Tua
     * (BkPemanggilanController). Sebelumnya keduanya sama-sama tidak
     * memeriksa apa pun, jadi memperbaiki satu saja akan menyisakan lubang
     * di pintu satunya.
     *
     * Dilempar sebagai ValidationException (bukan abort) supaya pengguna
     * kembali ke formnya dengan isian yang masih utuh, dan pesannya
     * menempel di kolom nomor urut.
     *
     * @param  int|null  $kecualikanId  id surat yang sedang diubah — supaya
     *                                  surat tidak dianggap bentrok dengan dirinya sendiri.
     */
    public static function pastikanBelumDipakai(string $nomorSurat, ?int $kecualikanId = null, string $namaField = 'nomor_urut'): void
    {
        $bentrok = Surat::nomorSudahDipakai($nomorSurat, $kecualikanId);

        if (! $bentrok) {
            return;
        }

        $milik = $bentrok->siswa?->nama ? " atas nama {$bentrok->siswa->nama}" : '';
        $tanggal = $bentrok->tanggal?->translatedFormat('d F Y');

        throw ValidationException::withMessages([
            $namaField => "Nomor surat {$nomorSurat} sudah dipakai{$milik}"
                .($tanggal ? " (surat tanggal {$tanggal})" : '')
                .'. Gunakan nomor urut yang lain.',
        ]);
    }
}
