<?php

namespace App\Imports;

use App\Models\JadwalPelajaran;
use App\Models\JamPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

/**
 * Format kolom excel (header baris 1):
 * hari | kode_kelas | jam_ke | kode_mapel | nip_guru
 * Contoh: Senin | 7A | 1 | MTK | 198501012010011001
 */
class JadwalImport implements ToModel, WithHeadingRow, SkipsEmptyRows
{
    public function __construct(private int $tahunAjaranId) {}

    public function model(array $row)
    {
        $kelas = Kelas::where('nama_kelas', trim($row['kode_kelas']))->first();
        $mapel = MataPelajaran::where('kode', trim($row['kode_mapel']))->first();
        $guru = User::where('nip', trim($row['nip_guru']))->where('role', 'guru')->first();
        $hari = ucfirst(strtolower(trim($row['hari'])));
        $jam = JamPelajaran::where('hari', $hari)->where('jam_ke', (int) $row['jam_ke'])->first();

        if (! $kelas || ! $mapel || ! $guru || ! $jam) {
            return null;
        }

        return JadwalPelajaran::updateOrCreate(
            [
                'hari' => $hari,
                'kelas_id' => $kelas->id,
                'jam_pelajaran_id' => $jam->id,
                'tahun_ajaran_id' => $this->tahunAjaranId,
            ],
            [
                'mata_pelajaran_id' => $mapel->id,
                'guru_id' => $guru->id,
            ]
        );
    }
}
