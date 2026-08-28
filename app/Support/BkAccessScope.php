<?php

namespace App\Support;

use App\Models\GuruBkKelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;

/**
 * Aturan cakupan akses modul BK (Bagian 20 spec):
 * - Admin, Kurikulum, Kepala Sekolah, Kesiswaan: semua siswa/kelas (view;
 *   hanya Admin & Guru BK yang boleh MENGUBAH data — dicek terpisah per
 *   aksi lewat middleware role di routes).
 * - Guru BK: siswa di kelas-kelas yang di-mapping-kan kepadanya
 *   (tabel guru_bk_kelas — reuse dari fitur monitoring absensi).
 * - Wali Kelas: siswa di kelasnya sendiri saja.
 * - Guru mapel biasa (bukan wali kelas, bukan BK): TIDAK dapat akses
 *   profil BK siswa manapun (kasus yang dia laporkan sendiri tetap bisa
 *   dilihat lewat listing kasus miliknya — diatur terpisah di controller).
 */
trait BkAccessScope
{
    protected function bkBisaAksesSiswa(User $user, Siswa $siswa): bool
    {
        if (in_array($user->role, ['admin', 'kurikulum', 'kepala_sekolah', 'kesiswaan'])) {
            return true;
        }
        if ($user->role === 'guru_bk') {
            return $user->kelasBk()->pluck('id')->contains($siswa->kelasIdSekarang());
        }
        if ($user->role === 'guru' && $user->isWaliKelas()) {
            return optional($user->kelasWali)->id === $siswa->kelasIdSekarang();
        }
        return false;
    }

    /** Daftar kelas_id yang boleh diakses user (dipakai untuk filter query listing). Null = boleh semua. */
    protected function bkKelasIdsUntukUser(User $user): ?array
    {
        if (in_array($user->role, ['admin', 'kurikulum', 'kepala_sekolah', 'kesiswaan'])) {
            return null; // semua kelas
        }
        if ($user->role === 'guru_bk') {
            return $user->kelasBk()->pluck('id')->toArray();
        }
        if ($user->role === 'guru' && $user->isWaliKelas()) {
            $kelasId = optional($user->kelasWali)->id;
            return $kelasId ? [$kelasId] : [];
        }
        return [];
    }

    /**
     * Validasi bahwa siswa yang dipilih memang berada dalam cakupan kelas
     * user (BK/wali kelas), konsisten dengan filter dropdown di create().
     * null atau [] dari bkKelasIdsUntukUser() berarti tidak dibatasi
     * (sesuai perilaku dropdown yang sudah ada), jadi hanya menolak kalau
     * cakupannya memang terbatas (non-kosong) TAPI siswa di luar itu.
     */
    protected function bkPastikanSiswaSesuaiCakupan(User $user, Siswa $siswa): void
    {
        $kelasIds = $this->bkKelasIdsUntukUser($user);
        if ($kelasIds !== null && $kelasIds !== [] && ! in_array($siswa->kelasIdSekarang(), $kelasIds, true)) {
            abort(403, 'Siswa ini di luar cakupan kelas Anda.');
        }
    }

    /**
     * Guru BK yang tampil sebagai penanda tangan di bagian Cetak (Kasus,
     * Pembinaan, Pemanggilan Orang Tua). TIDAK bisa dipilih manual lewat
     * dropdown:
     * - Kalau yang login memang akun Guru BK, tanda tangan SELALU akun itu
     *   sendiri — tidak pernah Guru BK lain — karena dropdown Kelas untuk
     *   akun Guru BK memang sudah dibatasi hanya ke kelas yang dia ampu
     *   sendiri (lihat $kelasList di controller), jadi kelas manapun yang
     *   dia pilih tetap kelasnya sendiri.
     * - Untuk role yang boleh lihat semua kelas (Admin/Kurikulum/Kepala
     *   Sekolah/Kesiswaan): kalau ada filter kelas_id dipilih, tanda tangan
     *   menyesuaikan Guru BK yang benar-benar mengampu kelas itu (tabel
     *   guru_bk_kelas, tahun ajaran aktif).
     * - Selain itu (mis. Admin lihat semua kelas tanpa filter, atau
     *   kelasnya belum di-mapping ke Guru BK manapun) dikembalikan null —
     *   kolom nama/NIP di cetakan otomatis tampil titik-titik, tidak salah
     *   atribusi.
     */
    protected function bkGuruBkUntukCetak(User $user, ?int $kelasId): ?User
    {
        if ($user->role === 'guru_bk') {
            return $user;
        }

        if ($kelasId) {
            $tahunAjaran = KonteksPeriode::pilihan();
            $mapping = GuruBkKelas::where('kelas_id', $kelasId)
                ->when($tahunAjaran, fn ($q) => $q->where('tahun_ajaran_id', $tahunAjaran->id))
                ->with('guru')
                ->first();
            if ($mapping && $mapping->guru) {
                return $mapping->guru;
            }
        }

        return null;
    }
}
