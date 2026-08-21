<?php

namespace App\Services;

use App\Models\GuruBkKelas;
use App\Models\GuruMengajarKelas;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\OrangTua;
use App\Models\PengaturanSekolah;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;

/**
 * Checklist "Hal yang perlu dilakukan" di bagian paling bawah dashboard
 * Admin & Kurikulum. Membantu memandu alur setup tahun ajaran dari nol
 * (Pengaturan Sekolah) sampai siap dipakai (Import Akun Orang Tua).
 *
 * Semua langkah yang berkaitan dengan kelas/mapping/jadwal di-scope ke
 * SATU "periode kerja" — tahun ajaran yang PALING BARU dibuat (bukan
 * hanya yang aktif), supaya checklist tetap relevan meski tahun ajaran
 * itu belum/sudah diaktifkan (Langkah 4: "Aktifkan Tahun Ajaran").
 */
class OnboardingChecklistService
{
    /**
     * @param  string|null  $role  Role user yang sedang login ('admin'/'kurikulum'/dst) — dipakai
     *                             supaya link "Kelola Pengguna" (khusus admin) tidak ditampilkan
     *                             untuk Kurikulum, meski status selesai/belumnya tetap ikut dihitung.
     * @return array{items: array<int, array{key: string, label: string, selesai: bool, route: ?string}>, selesai_semua: bool, jumlah_selesai: int, jumlah_total: int}
     */
    public function status(?string $role = null): array
    {
        $periodeKerja = TahunAjaran::orderByDesc('id')->first();

        $kelasPeriode = $periodeKerja ? Kelas::untukTahunAjaran($periodeKerja) : Kelas::whereRaw('1 = 0');
        $totalKelas = (clone $kelasPeriode)->count();
        $kelasTanpaWali = (clone $kelasPeriode)->whereNull('wali_kelas_id')->count();

        $siswaAktifDiKelasPeriode = $periodeKerja
            ? Siswa::where('is_active', true)
                ->whereHas('kelas', fn ($q) => $q->untukTahunAjaran($periodeKerja))
                ->exists()
            : false;

        $pengaturan = PengaturanSekolah::current();
        $pengaturanLengkap = filled($pengaturan->nama_sekolah)
            && filled($pengaturan->nama_kepala_sekolah)
            && filled($pengaturan->nip_kepala_sekolah);

        $penggunaLengkap = User::where('role', '!=', 'admin')->where('is_active', true)->exists();

        $items = [
            [
                'key' => 'pengaturan-sekolah',
                'label' => 'Pengaturan Sekolah',
                'selesai' => $pengaturanLengkap,
                'route' => route('pengaturan-sekolah.edit'),
            ],
            [
                'key' => 'kelola-pengguna',
                'label' => 'Cek/lengkapi Kelola Pengguna',
                'selesai' => $penggunaLengkap,
                'route' => $role === 'admin' ? route('users.index') : null,
            ],
            [
                'key' => 'tahun-ajaran-baru',
                'label' => 'Buat Tahun Ajaran Baru',
                'selesai' => (bool) $periodeKerja,
                'route' => route('tahun-ajaran.index'),
            ],
            [
                'key' => 'aktifkan-tahun-ajaran',
                'label' => 'Aktifkan Tahun Ajaran',
                'selesai' => (bool) ($periodeKerja?->is_active),
                'route' => route('tahun-ajaran.index'),
            ],
            [
                'key' => 'data-kelas',
                'label' => 'Buat Data Kelas',
                'selesai' => $totalKelas > 0,
                'route' => route('kelas.index'),
            ],
            [
                'key' => 'wali-kelas',
                'label' => 'Lengkapi Wali Kelas',
                'selesai' => $totalKelas > 0 && $kelasTanpaWali === 0,
                'route' => route('kelas.index'),
            ],
            [
                'key' => 'data-siswa',
                'label' => 'Input/Import Data Siswa (atau Kenaikan Kelas kalau lanjutan)',
                'selesai' => $siswaAktifDiKelasPeriode,
                'route' => route('siswa.index'),
            ],
            [
                'key' => 'guru-mengajar',
                'label' => 'Mapping Guru Mengajar',
                'selesai' => $periodeKerja ? GuruMengajarKelas::where('tahun_ajaran_id', $periodeKerja->id)->exists() : false,
                'route' => route('kurikulum.guru-mengajar.index'),
            ],
            [
                'key' => 'guru-bk',
                'label' => 'Guru BK per Kelas',
                'selesai' => $periodeKerja ? GuruBkKelas::where('tahun_ajaran_id', $periodeKerja->id)->exists() : false,
                'route' => route('kurikulum.guru-bk.index'),
            ],
            [
                'key' => 'jadwal',
                'label' => 'Susun Jadwal Pelajaran',
                'selesai' => $periodeKerja ? JadwalPelajaran::where('tahun_ajaran_id', $periodeKerja->id)->exists() : false,
                'route' => route('jadwal.index'),
            ],
            [
                'key' => 'akun-orang-tua',
                'label' => 'Import Akun Orang Tua',
                'selesai' => OrangTua::exists(),
                'route' => route('orangtua-akun.index'),
            ],
        ];

        $jumlahSelesai = collect($items)->where('selesai', true)->count();

        return [
            'items' => $items,
            'selesai_semua' => $jumlahSelesai === count($items),
            'jumlah_selesai' => $jumlahSelesai,
            'jumlah_total' => count($items),
        ];
    }
}
