<?php

namespace Database\Seeders;

use App\Models\MataPelajaran;
use Illuminate\Database\Seeder;

class MataPelajaranSeeder extends Seeder
{
    public function run(): void
    {
        $mapel = [
            ['MTK', 'Matematika'],
            ['BIN', 'Bahasa Indonesia'],
            ['BIG', 'Bahasa Inggris'],
            ['IPA', 'Ilmu Pengetahuan Alam'],
            ['IPS', 'Ilmu Pengetahuan Sosial'],
            ['PAI', 'Pendidikan Agama Islam'],
            ['PJOK', 'Pendidikan Jasmani, Olahraga dan Kesehatan'],
            ['PKN', 'Pendidikan Pancasila dan Kewarganegaraan'],
            ['SBD', 'Seni Budaya'],
            ['PRA', 'Prakarya'],
        ];

        foreach ($mapel as [$kode, $nama]) {
            MataPelajaran::updateOrCreate(['kode' => $kode], ['nama_mapel' => $nama]);
        }
    }
}
