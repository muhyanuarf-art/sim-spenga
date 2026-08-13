<?php

namespace App\Imports;

use App\Models\OrangTua;
use App\Models\Siswa;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Format kolom excel (header baris 1): nis
 *
 * Satu akun orang tua dibuat otomatis per NIS siswa yang cocok di database.
 * - NIS tidak ditemukan di tabel siswa -> baris dilewati (dicatat di $dilewati).
 * - NIS sudah punya akun orang tua sebelumnya -> dilewati (password TIDAK
 *   direset supaya perubahan password oleh orang tua tidak tertimpa saat
 *   import ulang). Reset password dilakukan manual oleh Admin per akun.
 * - Password default akun baru: "password" (di-hash), wajib diganti sendiri
 *   oleh orang tua setelah login pertama.
 */
class OrangTuaImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public const PASSWORD_DEFAULT = 'password';

    public int $dibuat = 0;

    /** @var array<int,string> NIS yang dilewati karena data siswa tidak ditemukan. */
    public array $dilewatiTidakDitemukan = [];

    /** @var array<int,string> NIS yang dilewati karena akun orang tua sudah ada. */
    public array $dilewatiSudahAda = [];

    public function collection(\Illuminate\Support\Collection $rows): void
    {
        foreach ($rows as $row) {
            $nis = isset($row['nis']) ? trim((string) $row['nis']) : '';
            if ($nis === '') {
                continue;
            }

            $siswa = Siswa::where('nis', $nis)->first();
            if (! $siswa) {
                $this->dilewatiTidakDitemukan[] = $nis;
                continue;
            }

            if (OrangTua::where('siswa_id', $siswa->id)->exists()) {
                $this->dilewatiSudahAda[] = $nis;
                continue;
            }

            OrangTua::create([
                'siswa_id' => $siswa->id,
                'nis' => $siswa->nis,
                'password' => self::PASSWORD_DEFAULT, // otomatis di-hash oleh cast 'hashed' pada model
            ]);
            $this->dibuat++;
        }
    }
}
