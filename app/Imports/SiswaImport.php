<?php

namespace App\Imports;

use App\Models\Kelas;
use App\Models\Siswa;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

/**
 * Format kolom excel (header baris 1):
 * nis | nisn | nama | jenis_kelamin | kode_kelas
 */
class SiswaImport implements ToModel, WithHeadingRow, SkipsEmptyRows
{
    public function model(array $row)
    {
        $kelas = Kelas::where('nama_kelas', trim($row['kode_kelas']))->first();
        if (! $kelas) {
            return null;
        }

        return Siswa::updateOrCreate(
            ['nis' => trim($row['nis'])],
            [
                'nisn' => $row['nisn'] ?? null,
                'nama' => trim($row['nama']),
                'jenis_kelamin' => strtoupper(trim($row['jenis_kelamin'])) === 'P' ? 'P' : 'L',
                'kelas_id' => $kelas->id,
                'is_active' => true,
            ]
        );
    }
}
