<?php

namespace App\Imports;

use App\Models\MataPelajaran;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Format kolom excel (header baris 1):
 * kode | nama_mapel
 * Contoh: MTK | Matematika
 */
class MataPelajaranImport implements ToModel, WithHeadingRow, SkipsEmptyRows
{
    public function model(array $row)
    {
        $kode = trim($row['kode'] ?? '');
        $nama = trim($row['nama_mapel'] ?? '');

        if ($kode === '' || $nama === '') {
            return null;
        }

        return MataPelajaran::updateOrCreate(
            ['kode' => $kode],
            ['nama_mapel' => $nama]
        );
    }
}
