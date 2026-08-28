<?php

namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\User;
use Illuminate\Database\Seeder;

class KelasSeeder extends Seeder
{
    public function run(): void
    {
        $guru = User::where('role', 'guru')->get();
        $waliIndex = 0;

        foreach ([7, 8, 9] as $tingkat) {
            foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $huruf) {
                $waliKelasId = null;
                if ($guru->count() && $waliIndex < $guru->count()) {
                    // hanya beberapa contoh kelas diberi wali kelas dari seeder
                    if ($tingkat == 7 && in_array($huruf, ['A', 'B'])) {
                        $waliKelasId = $guru[$waliIndex % $guru->count()]->id;
                        $waliIndex++;
                    }
                }

                $kelas = Kelas::updateOrCreate(
                    ['nama_kelas' => "{$tingkat}{$huruf}"],
                    ['tingkat' => $tingkat]
                );

                // Wali kelas kini penugasan per semester, bukan kolom di
                // tabel kelas — lihat App\Models\PenugasanWaliKelas.
                \App\Models\PenugasanWaliKelas::tetapkanLewatFormKelas(
                    $kelas->id,
                    $waliKelasId,
                    $kelas->tahunAjaran
                );
            }
        }
    }
}
