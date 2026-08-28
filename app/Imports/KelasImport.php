<?php

namespace App\Imports;

use App\Models\Kelas;
use App\Models\User;

/**
 * Format kolom excel (judul di baris 1):
 * nama_kelas | tingkat | nip_wali_kelas
 * Contoh    : 7A | 7 | 198501012010011001
 *
 * nip_wali_kelas boleh dikosongkan (opsional).
 *
 * STEP 5 — kelas terikat Tahun Ajaran; tujuannya dipilih di halaman
 * import dan dikirim lewat constructor, bukan kolom di Excel.
 */
class KelasImport extends ImportDasar
{
    public function __construct(private int $tahunAjaranId)
    {
        parent::__construct('data kelas');
    }

    protected function kolomWajib(): array
    {
        return ['nama_kelas', 'tingkat'];
    }

    protected function prosesBaris(array $data, int $baris): void
    {
        $namaKelas = $this->teks($data, 'nama_kelas');
        $tingkat = $this->angka($data, 'tingkat');

        if ($namaKelas === '') {
            $this->hasil->lewati($baris, 'Kolom "nama_kelas" kosong.');

            return;
        }

        if (! in_array($tingkat, [7, 8, 9], true)) {
            $this->hasil->lewati(
                $baris,
                'Kolom "tingkat" harus diisi 7, 8, atau 9 — terbaca "'.$this->teks($data, 'tingkat').'".',
                'Kelas '.$namaKelas
            );

            return;
        }

        // Wali kelas opsional. Kalau NIP diisi tapi tidak ketemu, barisnya
        // TETAP disimpan (kelasnya nyata) — tapi dilaporkan supaya operator
        // tahu wali kelasnya belum terpasang, bukan dibiarkan diam-diam.
        $waliKelasId = null;
        $nipWali = $this->teks($data, 'nip_wali_kelas');

        if ($nipWali !== '') {
            $wali = User::where('nip', $nipWali)->where('role', 'guru')->first();
            $waliKelasId = $wali?->id;

            if (! $wali) {
                $this->hasil->lewati(
                    $baris,
                    'Kelas tetap disimpan, TAPI wali kelas tidak terpasang: NIP "'.$nipWali
                        .'" tidak ditemukan sebagai guru di menu Kelola Pengguna.',
                    'Kelas '.$namaKelas
                );
            }
        }

        $this->catat(Kelas::updateOrCreate(
            [
                'tahun_ajaran_id' => $this->tahunAjaranId,
                'nama_kelas' => $namaKelas,
            ],
            [
                'tingkat' => $tingkat,
                'wali_kelas_id' => $waliKelasId,
            ]
        ));
    }
}
