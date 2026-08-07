<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            TahunAjaranSeeder::class,
            KelasSeeder::class,
            MataPelajaranSeeder::class,
            JamPelajaranSeeder::class,
            SiswaSeeder::class,
        ]);
    }
}
