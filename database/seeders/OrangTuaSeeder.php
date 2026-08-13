<?php

namespace Database\Seeders;

use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OrangTuaSeeder extends Seeder
{
    /**
     * Contoh akun demo Orang Tua, ditautkan ke 2 siswa pertama kelas 7A
     * (dari SiswaSeeder) supaya alur portal Orang Tua bisa langsung dicoba.
     */
    public function run(): void
    {
        $anakContoh = Siswa::whereHas('kelas', fn ($q) => $q->where('nama_kelas', '7A'))
            ->orderBy('nis')
            ->take(2)
            ->get();

        if ($anakContoh->isEmpty()) {
            return; // SiswaSeeder belum jalan / kelas 7A belum ada
        }

        $ortu = User::updateOrCreate(
            ['email' => 'ortu@spenga.sch.id'],
            [
                'name' => 'Orang Tua Contoh',
                'password' => Hash::make('password'),
                'role' => 'orang_tua',
            ]
        );

        $ortu->anakAsuh()->sync($anakContoh->pluck('id'));
    }
}
