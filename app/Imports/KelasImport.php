<?php

namespace App\Imports;

use App\Models\Kelas;
use App\Models\User;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Format kolom excel (header baris 1):
 * nama_kelas | tingkat | nip_wali_kelas
 * Contoh   : 7A | 7 | 198501012010011001
 *
 * nip_wali_kelas boleh dikosongkan (opsional).
 *
 * STEP 5 — kelas sekarang terikat Tahun Ajaran (tujuan dipilih di
 * halaman import, dikirim lewat constructor), bukan lagi kolom di Excel.
 */
class KelasImport implements ToModel, WithHeadingRow, SkipsEmptyRows
{
    public function __construct(private int $tahunAjaranId) {}

    public function model(array $row)
    {
        $namaKelas = trim($row['nama_kelas'] ?? '');
        $tingkat = (int) ($row['tingkat'] ?? 0);

        if ($namaKelas === '' || ! in_array($tingkat, [7, 8, 9], true)) {
            return null; // baris dilewati jika data wajib tidak valid
        }

        $waliKelasId = null;
        if (! empty($row['nip_wali_kelas'])) {
            $wali = User::where('nip', trim($row['nip_wali_kelas']))->where('role', 'guru')->first();
            $waliKelasId = $wali?->id;
        }

        return Kelas::updateOrCreate(
            [
                'tahun_ajaran_id' => $this->tahunAjaranId,
                'nama_kelas' => $namaKelas,
            ],
            [
                'tingkat' => $tingkat,
                'wali_kelas_id' => $waliKelasId,
            ]
        );
    }
}
