<?php

namespace App\Imports;

use App\Models\Kelas;
use App\Models\Siswa;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

/**
 * Format kolom excel (header baris 1):
 * nis | nisn | nama | nama_ortu | no_wa_ortu | jenis_kelamin | kode_kelas
 */
class SiswaImport implements ToModel, WithHeadingRow, SkipsEmptyRows
{
    /** @var array<int,string> NIS yang dilewati karena kode_kelas tidak ditemukan. */
    public array $dilewatiKelasTidakDitemukan = [];

    public function model(array $row)
    {
        $kelas = Kelas::where('nama_kelas', trim($row['kode_kelas']))->first();
        if (! $kelas) {
            $this->dilewatiKelasTidakDitemukan[] = trim($row['nis'] ?? '');
            return null;
        }

        return Siswa::updateOrCreate(
            ['nis' => trim($row['nis'])],
            [
                'nisn' => $row['nisn'] ?? null,
                'nama' => trim($row['nama']),
                'nama_ortu' => isset($row['nama_ortu']) ? trim($row['nama_ortu']) : null,
                'no_wa_ortu' => isset($row['no_wa_ortu']) ? trim((string) $row['no_wa_ortu']) : null,
                'jenis_kelamin' => strtoupper(trim($row['jenis_kelamin'])) === 'P' ? 'P' : 'L',
                'kelas_id' => $kelas->id,
                'is_active' => true,
            ]
        );
    }
}
