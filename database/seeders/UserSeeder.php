<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@spenga.sch.id'],
            [
                'name' => 'Administrator',
                'nip' => 'ADM001',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'kepsek@spenga.sch.id'],
            [
                'name' => 'Kepala Sekolah',
                'nip' => 'KS001',
                'password' => Hash::make('password'),
                'role' => 'kepala_sekolah',
            ]
        );

        User::updateOrCreate(
            ['email' => 'kurikulum@spenga.sch.id'],
            [
                'name' => 'Tim Kurikulum',
                'nip' => 'KUR001',
                'password' => Hash::make('password'),
                'role' => 'kurikulum',
            ]
        );

        // Contoh guru (2 di antaranya sekaligus wali kelas, diatur di KelasSeeder)
        $namaGuru = [
            ['nip' => '198501012010011001', 'name' => 'Budi Santoso, S.Pd'],
            ['nip' => '198602022011012002', 'name' => 'Siti Aminah, S.Pd'],
            ['nip' => '198703032012013003', 'name' => 'Ahmad Fauzi, S.Pd'],
            ['nip' => '198804042013014004', 'name' => 'Dewi Lestari, S.Pd'],
        ];

        foreach ($namaGuru as $i => $guru) {
            User::updateOrCreate(
                ['email' => 'guru' . ($i + 1) . '@spenga.sch.id'],
                [
                    'name' => $guru['name'],
                    'nip' => $guru['nip'],
                    'password' => Hash::make('password'),
                    'role' => 'guru',
                ]
            );
        }

        // Salah satu guru dijadikan contoh akun Kesiswaan (role view-only,
        // lihat dashboard yang sama persis dengan Kurikulum). Di database
        // sungguhan, admin cukup ganti role guru manapun lewat menu
        // Kelola Pengguna -> Edit -> pilih role "Kesiswaan".
        User::where('email', 'guru4@spenga.sch.id')->update(['role' => 'kesiswaan']);
    }
}
