<?php

namespace App\Support;

use App\Models\Kelas;

/**
 * Menyusun pesan penolakan akses kelas yang MENJELASKAN sebabnya.
 *
 * Sejak ada pemilih periode dan kelas ikut semester (migrasi
 * 2026_08_29_000001), sebab paling sering seseorang ditolak membuka sebuah
 * kelas BUKAN karena haknya kurang, melainkan karena kelas itu milik
 * SEMESTER LAIN — biasanya dari tautan/bookmark lama, atau karena periode
 * yang sedang dilihat baru saja diganti.
 *
 * Pesan lama "Anda tidak memiliki akses ke kelas ini" menyesatkan di
 * keadaan itu: pengguna mengira haknya dicabut, padahal cukup mengganti
 * pemilih periode di kanan atas.
 */
class PesanAksesKelas
{
    public static function tolak(?int $kelasId): string
    {
        $kelas = $kelasId ? Kelas::with('tahunAjaran')->find($kelasId) : null;
        $pilihan = KonteksPeriode::pilihan();

        // Kelas ada, tapi milik periode lain -> itulah sebab sebenarnya.
        if ($kelas && $pilihan && $kelas->tahun_ajaran_id !== $pilihan->id) {
            $milik = $kelas->tahunAjaran?->labelPeriode() ?? 'periode lain';

            return "Kelas {$kelas->nama_kelas} milik {$milik}, sedangkan Anda sedang melihat "
                .$pilihan->labelPeriode().'. Ganti periode lewat pemilih di kanan atas untuk membukanya.';
        }

        return 'Anda tidak memiliki akses ke kelas ini.';
    }
}
