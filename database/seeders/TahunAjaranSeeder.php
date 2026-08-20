<?php

namespace Database\Seeders;

use App\Models\TahunAjaran;
use Illuminate\Database\Seeder;

class TahunAjaranSeeder extends Seeder
{
    public function run(): void
    {
        TahunAjaran::updateOrCreate(
            ['nama' => '2025/2026', 'semester' => 'Ganjil'],
            ['is_active' => true, 'status' => TahunAjaran::STATUS_AKTIF]
        );
        TahunAjaran::updateOrCreate(
            ['nama' => '2025/2026', 'semester' => 'Genap'],
            ['is_active' => false, 'status' => TahunAjaran::STATUS_AKAN_DATANG]
        );
    }
}
