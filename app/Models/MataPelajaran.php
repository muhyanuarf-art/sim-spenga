<?php

namespace App\Models;

use App\Models\Concerns\MilikTahunAjaran;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MataPelajaran extends Model
{
    use HasFactory;
    // Master data per tahun ajaran — lihat trait & migrasi 2026_08_28_000003.
    use MilikTahunAjaran;

    protected $table = 'mata_pelajarans';

    protected $fillable = ['tahun_ajaran_id', 'kode', 'nama_mapel'];

    /**
     * Label pendek untuk kepala kolom yang sempit — dipakai lembar Nilai
     * Rapor Kelas, yang menampilkan satu kolom per mata pelajaran.
     *
     * Di sana nama panjang seperti "Pendidikan Jasmani, Olahraga dan
     * Kesehatan" (42 huruf) tidak muat di kolom selebar 36px: teksnya
     * meluber keluar kotak kepala tabel dan menimpa baris siswa di
     * bawahnya. Yang dipakai di sini singkatannya saja — nama utuhnya
     * tetap muncul sebagai tooltip saat kolomnya disentuh.
     *
     * Urutan pemilihannya:
     *   1. Kolom `kode` bila sekolah sudah mengisinya. Itu singkatan yang
     *      memang mereka pakai sehari-hari (BIN, IPA, PJOK), jadi selalu
     *      lebih dikenali daripada singkatan buatan sistem.
     *   2. Nama apa adanya bila memang sudah pendek.
     *   3. Huruf awal tiap kata sebagai jalan terakhir, mis. "Ilmu
     *      Pengetahuan Alam" → "IPA".
     */
    public function labelRingkas(int $maks = 12): string
    {
        $kode = trim((string) $this->kode);

        if ($kode !== '') {
            return mb_strtoupper($kode);
        }

        $nama = trim((string) $this->nama_mapel);

        if ($nama === '' || mb_strlen($nama) <= $maks) {
            return $nama;
        }

        // Kata sambung tidak ikut disingkat — "Pendidikan Jasmani, Olahraga
        // DAN Kesehatan" seharusnya jadi PJOK, bukan PJODK.
        $sambung = ['dan', 'atau', 'yang', 'di', 'ke', 'dari', 'untuk', 'pada', 'serta', 'the', 'of', '&'];

        $kata = collect(preg_split('/[\s,\.\-\/]+/u', $nama) ?: [])
            ->map(fn ($k) => trim($k))
            ->filter(fn ($k) => $k !== '' && ! in_array(mb_strtolower($k), $sambung, true))
            ->values();

        if ($kata->count() >= 2) {
            return $kata->map(fn ($k) => mb_strtoupper(mb_substr($k, 0, 1)))->implode('');
        }

        // Satu kata tetapi panjang, mis. "Kewarganegaraan". Menyingkatnya jadi
        // satu huruf tidak berguna, dan memotongnya di tengah ("Kewarganegar")
        // terbaca seperti salah ketik. Dibiarkan utuh saja: kolomnya sudah
        // diberi batas lebar dan boleh membungkus ke baris berikutnya, jadi
        // kata sepanjang apa pun melebar ke bawah di dalam kotaknya sendiri —
        // tidak pernah menimpa baris siswa.
        return $nama;
    }
}
