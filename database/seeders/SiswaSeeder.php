<?php

namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Database\Seeder;

class SiswaSeeder extends Seeder
{
    public function run(): void
    {
        $namaDepan = ['Ahmad', 'Muhammad', 'Andi', 'Budi', 'Citra', 'Dewi', 'Eka', 'Fajar', 'Gita', 'Hana', 'Indra', 'Joko', 'Kartika', 'Lestari', 'Maya'];
        $namaBelakang = ['Pratama', 'Saputra', 'Wijaya', 'Kusuma', 'Ramadhan', 'Anggraini', 'Nugroho', 'Setiawan', 'Rahayu', 'Permata'];

        // Hanya seed siswa untuk 2 kelas contoh (7A dan 7B) agar seeder ringan
        $kelasContoh = Kelas::whereIn('nama_kelas', ['7A', '7B'])->get();

        $nisCounter = 24001;
        foreach ($kelasContoh as $kelas) {
            for ($i = 1; $i <= 30; $i++) {
                $jk = $i % 2 === 0 ? 'P' : 'L';
                $nis = (string) $nisCounter++;
                Siswa::updateOrCreate(
                    ['nis' => $nis],
                    [
                        'nisn' => (string) (1000000000 + (int) $nis),
                        'nama' => $namaDepan[array_rand($namaDepan)] . ' ' . $namaBelakang[array_rand($namaBelakang)],
                        'jenis_kelamin' => $jk,
                        'kelas_id' => $kelas->id,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
