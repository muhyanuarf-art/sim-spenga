<?php

namespace App\Imports;

use App\Models\GuruMengajarKelas;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

/**
 * Format kolom excel (header baris 1):
 * nip_guru | kode_kelas | kode_mapel
 * Contoh   : 198501012010011001 | 7A | MTK
 */
class GuruMengajarImport implements ToModel, WithHeadingRow, SkipsEmptyRows
{
    public function __construct(private int $tahunAjaranId) {}

    public function model(array $row)
    {
        $guru = User::where('nip', trim($row['nip_guru']))->where('role', 'guru')->first();
        $kelas = Kelas::where('nama_kelas', trim($row['kode_kelas']))->first();
        $mapel = MataPelajaran::where('kode', trim($row['kode_mapel']))->first();

        if (! $guru || ! $kelas || ! $mapel) {
            return null; // baris dilewati jika data referensi tidak ditemukan
        }

        return new GuruMengajarKelas([
            'guru_id' => $guru->id,
            'kelas_id' => $kelas->id,
            'mata_pelajaran_id' => $mapel->id,
            'tahun_ajaran_id' => $this->tahunAjaranId,
        ]);
    }
}
