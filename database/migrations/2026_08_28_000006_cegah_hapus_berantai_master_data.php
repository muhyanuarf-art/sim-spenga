<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * CEGAH HAPUS BERANTAI PADA MASTER DATA.
 *
 * =====================================================================
 * MASALAH YANG DIPERBAIKI
 * =====================================================================
 * Seluruh controller master data sebenarnya SUDAH punya penjaga hapus
 * (Controller::hapusAtauGagalDenganPesan) yang seharusnya menolak dengan
 * pesan "tidak dapat dihapus karena masih dipakai...". Penjaga itu
 * bekerja dengan menangkap QueryException 23000 dari database.
 *
 * Tapi foreign key-nya dibuat ON DELETE CASCADE. Dengan CASCADE, database
 * TIDAK PERNAH melempar error — ia justru diam-diam menghapus seluruh
 * baris anaknya. Jadi penjaganya tidak pernah berjalan sama sekali, dan
 * pengguna malah melihat pesan "berhasil dihapus".
 *
 * Terbukti pada pengujian 28 Agustus 2026:
 *   hapus 1 mata pelajaran -> "berhasil dihapus", 2 jadwal ikut lenyap
 *   hapus 1 jam pelajaran  -> "berhasil dihapus", sisa jadwal ikut lenyap
 *
 * Yang paling berbahaya: menghapus SATU mata pelajaran ikut menghapus
 * SELURUH nilai siswa untuk mapel itu (nilai_siswas), di semua periode,
 * termasuk semester yang sudah ditutup & dikunci.
 *
 * =====================================================================
 * YANG DIUBAH JADI RESTRICT
 * =====================================================================
 * Semua penunjuk dari DATA TRANSAKSI & PENUGASAN ke MASTER DATA. Setelah
 * ini, menghapus master yang masih dipakai akan ditolak database, penjaga
 * di controller kembali hidup, dan pengguna mendapat pesan yang jelas.
 *
 * =====================================================================
 * YANG SENGAJA DIBIARKAN CASCADE
 * =====================================================================
 * Baris yang memang MILIK induknya dan tidak punya arti sendiri:
 *   kegiatan_kelas          -> kelas   (daftar sasaran kegiatan)
 *   notifikasi_was          -> kelas   (log kirim WhatsApp)
 *   penugasan_wali_kelas    -> kelas   (penugasan ikut kelasnya)
 *   orang_tua_siswa         -> users   (tabel pivot akun)
 *   orang_tuas              -> siswas  (akun portal milik siswa itu)
 *   ekstrakurikuler_siswas  -> siswas  (keanggotaan)
 *   notifikasi_alfa_*       -> siswas  (log)
 * Menghapus siswa & kelas sendiri sudah dijaga RESTRICT dari
 * riwayat_kelas_siswas dan absensi_siswas, jadi hanya baris yang benar-
 * benar masih kosong yang bisa dihapus.
 *
 * =====================================================================
 * BONUS: PEMBINA EKSTRAKURIKULER (temuan B4)
 * =====================================================================
 * ekstrakurikuler_pembinas -> ekstrakurikulers dulu NO ACTION, sehingga
 * kegiatan yang salah dibuat TIDAK PERNAH bisa dihapus selama masih
 * punya pembina. Diubah jadi CASCADE: pembina memang milik kegiatan itu
 * dan tidak berarti apa-apa tanpanya. Kegiatan yang absensinya sudah
 * terisi tetap tidak bisa dihapus, karena absensi_ekskul_pesertas
 * menunjuk baris pembina dengan RESTRICT.
 */
return new class extends Migration
{
    /** [tabel anak, kolom, tabel induk, aturan hapus baru] */
    private const ATURAN = [
        // --- ke mata_pelajarans ---
        ['nilai_siswas', 'mata_pelajaran_id', 'mata_pelajarans', 'RESTRICT'],
        ['jadwal_pelajarans', 'mata_pelajaran_id', 'mata_pelajarans', 'RESTRICT'],
        ['jurnal_mengajars', 'mata_pelajaran_id', 'mata_pelajarans', 'RESTRICT'],
        ['guru_mengajar_kelas', 'mata_pelajaran_id', 'mata_pelajarans', 'RESTRICT'],
        ['analisis_sumatifs', 'mata_pelajaran_id', 'mata_pelajarans', 'RESTRICT'],
        ['penilaian_kelas_mapels', 'mata_pelajaran_id', 'mata_pelajarans', 'RESTRICT'],

        // --- ke jam_pelajarans ---
        ['jadwal_pelajarans', 'jam_pelajaran_id', 'jam_pelajarans', 'RESTRICT'],
        ['jurnal_mengajars', 'jam_pelajaran_id', 'jam_pelajarans', 'RESTRICT'],

        // --- ke users (guru) ---
        ['jadwal_pelajarans', 'guru_id', 'users', 'RESTRICT'],
        ['guru_mengajar_kelas', 'guru_id', 'users', 'RESTRICT'],
        ['guru_bk_kelas', 'guru_id', 'users', 'RESTRICT'],
        ['penugasan_wali_kelas', 'guru_id', 'users', 'RESTRICT'],
        ['ekstrakurikuler_pembinas', 'user_id', 'users', 'RESTRICT'],
        ['evaluasi_pembinaans', 'petugas_id', 'users', 'RESTRICT'],

        // --- ke kelas ---
        ['nilai_siswas', 'kelas_id', 'kelas', 'RESTRICT'],
        ['analisis_sumatifs', 'kelas_id', 'kelas', 'RESTRICT'],
        ['penilaian_kelas_mapels', 'kelas_id', 'kelas', 'RESTRICT'],
        ['absensi_kegiatans', 'kelas_id', 'kelas', 'RESTRICT'],

        // --- pembina ekstrakurikuler (B4) ---
        ['ekstrakurikuler_pembinas', 'ekstrakurikuler_id', 'ekstrakurikulers', 'CASCADE'],
    ];

    /** Aturan semula, untuk down(). */
    private const SEMULA = [
        ['ekstrakurikuler_pembinas', 'ekstrakurikuler_id', 'ekstrakurikulers', 'NO ACTION'],
    ];

    public function up(): void
    {
        foreach (self::ATURAN as [$anak, $kolom, $induk, $aturan]) {
            $this->pasangAturan($anak, $kolom, $induk, $aturan);
        }
    }

    public function down(): void
    {
        foreach (self::ATURAN as [$anak, $kolom, $induk, $aturan]) {
            $semula = 'CASCADE';
            foreach (self::SEMULA as [$a, $k, , $s]) {
                if ($a === $anak && $k === $kolom) {
                    $semula = $s;
                }
            }
            $this->pasangAturan($anak, $kolom, $induk, $semula);
        }
    }

    /**
     * Ganti aturan ON DELETE sebuah foreign key: buang constraint lamanya,
     * pasang lagi dengan aturan baru. Indeks kolomnya sengaja TIDAK ikut
     * dibuang supaya query yang mengandalkannya tetap cepat.
     */
    private function pasangAturan(string $anak, string $kolom, string $induk, string $aturan): void
    {
        $nama = $this->namaConstraint($anak, $kolom, $induk);

        if ($nama) {
            DB::statement("ALTER TABLE `{$anak}` DROP FOREIGN KEY `{$nama}`");
        }

        $namaBaru = $nama ?: "{$anak}_{$kolom}_foreign";
        $onDelete = $aturan === 'NO ACTION' ? 'NO ACTION' : $aturan;

        DB::statement(
            "ALTER TABLE `{$anak}` ADD CONSTRAINT `{$namaBaru}` "
            ."FOREIGN KEY (`{$kolom}`) REFERENCES `{$induk}` (`id`) ON DELETE {$onDelete}"
        );
    }

    private function namaConstraint(string $anak, string $kolom, string $induk): ?string
    {
        $baris = DB::selectOne(
            'SELECT rc.CONSTRAINT_NAME nama
               FROM information_schema.REFERENTIAL_CONSTRAINTS rc
               JOIN information_schema.KEY_COLUMN_USAGE k
                 ON k.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
                AND k.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA
                AND k.TABLE_NAME = rc.TABLE_NAME
              WHERE rc.CONSTRAINT_SCHEMA = DATABASE()
                AND rc.TABLE_NAME = ?
                AND k.COLUMN_NAME = ?
                AND rc.REFERENCED_TABLE_NAME = ?
              LIMIT 1',
            [$anak, $kolom, $induk]
        );

        return $baris->nama ?? null;
    }
};
