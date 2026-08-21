<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STEP 7 Bagian 30 — TEMUAN AUDIT PALING SERIUS.
 *
 * Audit menemukan: kolom kelas_id, tahun_ajaran_id, dan siswa_id pada
 * TABEL TRANSAKSI/HISTORI masih `cascadeOnDelete()` ke induknya
 * (kelas/tahun_ajarans/siswas). Ini bertentangan langsung dengan prinsip
 * "histori tidak boleh hilang" yang dipegang sejak STEP 1.
 *
 * Contoh rantai bahaya nyata sebelum migrasi ini:
 *   hapus 1 baris tahun_ajarans (yang belum terkunci — TahunAjaranController
 *   ::destroy() hanya menolak yang SUDAH terkunci, tidak mengecek data
 *   terkait) → cascade ke SEMUA baris `kelas` tahun itu → cascade lagi ke
 *   SEMUA `siswas` di kelas itu (siswa TERHAPUS PERMANEN) → cascade lagi
 *   ke seluruh riwayat_kelas_siswas, kasus_siswas, pembinaan_siswas,
 *   pengurangan_poin_siswas, pemanggilan_orangtuas, absensi_siswas,
 *   orang_tuas milik siswa itu.
 *   Satu klik hapus tahun ajaran yang "kelihatannya aman" (belum
 *   dikunci) bisa memusnahkan data siswa & histori BK secara permanen
 *   tanpa peringatan apa pun.
 *
 * PERBAIKAN: ganti cascadeOnDelete() → restrictOnDelete() pada kolom-kolom
 * tsb. Efeknya: percobaan hapus kelas/tahun-ajaran/siswa yang MASIH
 * PUNYA data terkait akan DITOLAK oleh database (bukan diam-diam
 * mencascade), dan controller yang relevan (KelasController,
 * TahunAjaranController — sudah pakai helper hapusAtauGagalDenganPesan()
 * sejak awal, HANYA BELUM PERNAH benar-benar teruji karena cascade
 * sebelumnya tidak pernah melempar error) akan menampilkan pesan ramah
 * "masih memiliki data terkait", bukan diam-diam menghapus histori.
 *
 * SiswaController::destroy() JUGA diperbaiki di commit yang sama (lihat
 * app/Http/Controllers/SiswaController.php) untuk memakai helper yang
 * sama — sebelumnya destroy() memanggil $siswa->delete() TANPA
 * perlindungan apa pun.
 *
 * TIDAK ADA DATA YANG DIUBAH/DIHAPUS oleh migrasi ini — murni mengganti
 * ATURAN apa yang terjadi PADA SAAT NANTI ada percobaan hapus.
 *
 * Kolom yang SENGAJA TIDAK disentuh (di luar cakupan bug ini):
 * - guru_id/mata_pelajaran_id/jam_pelajaran_id pada jurnal_mengajars, dan
 *   FK lain yang menunjuk ke `users`/data master non-periode — migrasi
 *   2026_08_15_000001 sudah menangani sisi user_id dengan nullOnDelete().
 * - orang_tuas.siswa_id (akun orang tua memang terikat 1:1 ke siswa,
 *   wajar ikut terhapus kalau siswa itu sendiri benar-benar dihapus).
 * - notifikasi_alfa_terkirims.siswa_id (sekadar log notifikasi, bukan
 *   data akademik/histori inti).
 */
return new class extends Migration
{
    /** @var array<string, array<int, string>> tabel => daftar kolom yang diubah */
    private array $peta = [
        'kelas' => ['tahun_ajaran_id'],
        'siswas' => ['kelas_id'],
        'guru_mengajar_kelas' => ['kelas_id', 'tahun_ajaran_id'],
        'jadwal_pelajarans' => ['kelas_id', 'tahun_ajaran_id'],
        'jurnal_mengajars' => ['kelas_id'],
        'absensi_siswas' => ['kelas_id', 'siswa_id'],
        'guru_bk_kelas' => ['kelas_id', 'tahun_ajaran_id'],
        'kasus_siswas' => ['tahun_ajaran_id', 'siswa_id'],
        'pembinaan_siswas' => ['tahun_ajaran_id', 'siswa_id'],
        'pengurangan_poin_siswas' => ['tahun_ajaran_id', 'siswa_id'],
        'pemanggilan_orangtuas' => ['tahun_ajaran_id', 'siswa_id'],
        'riwayat_kelas_siswas' => ['tahun_ajaran_id', 'kelas_id', 'siswa_id'],
    ];

    private function referensiTabel(string $kolom): string
    {
        return match (true) {
            $kolom === 'kelas_id' => 'kelas',
            $kolom === 'tahun_ajaran_id' => 'tahun_ajarans',
            $kolom === 'siswa_id' => 'siswas',
            default => throw new \RuntimeException("Kolom tidak dikenal: {$kolom}"),
        };
    }

    public function up(): void
    {
        foreach ($this->peta as $tabel => $kolomList) {
            Schema::table($tabel, function (Blueprint $table) use ($kolomList) {
                foreach ($kolomList as $kolom) {
                    $table->dropForeign([$kolom]);
                }
            });
            Schema::table($tabel, function (Blueprint $table) use ($kolomList) {
                foreach ($kolomList as $kolom) {
                    $table->foreign($kolom)
                        ->references('id')->on($this->referensiTabel($kolom))
                        ->restrictOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->peta as $tabel => $kolomList) {
            Schema::table($tabel, function (Blueprint $table) use ($kolomList) {
                foreach ($kolomList as $kolom) {
                    $table->dropForeign([$kolom]);
                }
            });
            Schema::table($tabel, function (Blueprint $table) use ($kolomList) {
                foreach ($kolomList as $kolom) {
                    $table->foreign($kolom)
                        ->references('id')->on($this->referensiTabel($kolom))
                        ->cascadeOnDelete();
                }
            });
        }
    }
};
