<?php

namespace Database\Seeders;

use App\Models\JamPelajaran;
use Illuminate\Database\Seeder;

class JamPelajaranSeeder extends Seeder
{
    public function run(): void
    {
        // Senin - Kamis & Sabtu: 8 jam pelajaran biasa
        $jadwalReguler = [
            [1, '07:00', '07:40'],
            [2, '07:40', '08:20'],
            [3, '08:20', '09:00'],
            [4, '09:15', '09:55'],
            [5, '09:55', '10:35'],
            [6, '10:35', '11:15'],
            [7, '12:00', '12:40'],
            [8, '12:40', '13:20'],
        ];

        // Jumat: lebih singkat karena ada sholat Jumat, hanya 5 jam pelajaran
        $jadwalJumat = [
            [1, '07:00', '07:40'],
            [2, '07:40', '08:20'],
            [3, '08:20', '09:00'],
            [4, '09:15', '09:55'],
            [5, '09:55', '10:35'],
        ];

        $hariReguler = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Sabtu'];

        foreach ($hariReguler as $hari) {
            foreach ($jadwalReguler as [$jamKe, $mulai, $selesai]) {
                JamPelajaran::updateOrCreate(
                    ['hari' => $hari, 'jam_ke' => $jamKe],
                    [
                        'jam_mulai' => $mulai,
                        'jam_selesai' => $selesai,
                        'is_active' => true,
                    ]
                );
            }
        }

        foreach ($jadwalJumat as [$jamKe, $mulai, $selesai]) {
            JamPelajaran::updateOrCreate(
                ['hari' => 'Jumat', 'jam_ke' => $jamKe],
                [
                    'jam_mulai' => $mulai,
                    'jam_selesai' => $selesai,
                    'is_active' => true,
                ]
            );
        }
    }
}
