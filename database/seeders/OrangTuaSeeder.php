<?php

namespace Database\Seeders;

use App\Models\OrangTua;
use App\Models\Siswa;
use Illuminate\Database\Seeder;

class OrangTuaSeeder extends Seeder
{
    /**
     * Contoh akun demo Orang Tua (login pakai NIS), ditautkan ke 2 siswa
     * pertama kelas 7A (dari SiswaSeeder) supaya alur portal Orang Tua
     * bisa langsung dicoba. Password sama untuk semua akun demo: "password".
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

        foreach ($anakContoh as $siswa) {
            OrangTua::updateOrCreate(
                ['siswa_id' => $siswa->id],
                [
                    'nis' => $siswa->nis,
                    'password' => 'password',
                ]
            );
        }
    }
}
