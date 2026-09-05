<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * TIDAK ADA DATA YANG BOLEH HILANG KARENA SATU TOMBOL HAPUS.
 *
 * =====================================================================
 * MASALAHNYA
 * =====================================================================
 * Data sekolah dipakai seterusnya — nilai, prestasi, kasus BK, dan
 * daftar mata pelajaran tahun lalu tetap perlu dibaca bertahun-tahun
 * kemudian. Tetapi tombol Hapus di menu Tahun Ajaran bersandar sepenuhnya
 * pada aturan foreign key, dan aturannya belum seragam:
 *
 *   RESTRICT (12 tabel) — benar. Penghapusan ditolak, pesannya jelas.
 *   CASCADE   (8 tabel) — anak ikut terhapus, TANPA peringatan apa pun.
 *   SET NULL  (7 tabel) — barisnya selamat tetapi kehilangan tanda tahun
 *                         ajarannya, sehingga tidak lagi muncul di
 *                         penyaringan per semester. Tidak terhapus, tetapi
 *                         tidak lagi bisa ditemukan — praktis sama saja.
 *
 * Selama ini bencananya tidak pernah terjadi karena `kelas` memakai
 * RESTRICT dan tahun ajaran yang sudah dipakai pasti punya kelas. Jadi
 * penolakan itu datang dari kebetulan, bukan dari aturan. Kebetulan bukan
 * pengaman.
 *
 * =====================================================================
 * KEPUTUSANNYA
 * =====================================================================
 * Satu aturan untuk semua: SEBUAH TAHUN AJARAN HANYA BISA DIHAPUS BILA
 * TIDAK ADA APA PUN YANG MENUNJUK KEPADANYA. Semua tabel data diubah
 * menjadi RESTRICT.
 *
 * Tombol Hapus jadi hampir tidak pernah bisa dipakai, dan itu memang
 * maksudnya: gunanya hanya membatalkan tahun ajaran yang baru dibuat
 * karena salah ketik. Untuk mengakhiri semester yang sudah berjalan,
 * yang benar adalah Tutup Semester — bukan Hapus.
 *
 * Pesan penolakannya tidak perlu diubah: App\Support\PemakaiData membaca
 * aturan foreign key langsung dari information_schema, jadi tabel yang
 * baru dilindungi otomatis ikut disebut namanya.
 *
 * SATU PENGECUALIAN: `arsip_semesters` tetap CASCADE. Isinya bukan data
 * sekolah melainkan salinan laporan yang selalu bisa dibuat ulang dari
 * datanya. Berkas ZIP-nya dibersihkan TahunAjaranController::destroy()
 * supaya tidak tertinggal di disk tanpa pemilik.
 */
return new class extends Migration
{
    /** Tabel data => aturan lamanya, untuk dikembalikan bila migrasi dibatalkan. */
    private const TABEL = [
        // Dulu SET NULL — barisnya selamat tetapi tahun ajarannya hilang.
        'ekstrakurikuler_pembinas' => 'SET NULL',
        'ekstrakurikulers' => 'SET NULL',
        'jam_pelajarans' => 'SET NULL',
        'jenis_pelanggarans' => 'SET NULL',
        'jenis_surats' => 'SET NULL',
        'mata_pelajarans' => 'SET NULL',
        'prestasi_siswas' => 'SET NULL',

        // Dulu CASCADE — ikut terhapus diam-diam.
        'analisis_sumatifs' => 'CASCADE',
        'anggota_kelas' => 'CASCADE',
        'kktp_tingkats' => 'CASCADE',
        'nilai_siswas' => 'CASCADE',
        'pengaturan_penilaians' => 'CASCADE',
        'penilaian_kelas_mapels' => 'CASCADE',
        'penugasan_wali_kelas' => 'CASCADE',
    ];

    public function up(): void
    {
        foreach (array_keys(self::TABEL) as $tabel) {
            $this->pasangAturan($tabel, 'RESTRICT');
        }
    }

    public function down(): void
    {
        foreach (self::TABEL as $tabel => $aturanLama) {
            $this->pasangAturan($tabel, $aturanLama);
        }
    }

    /**
     * Ganti aturan ON DELETE sebuah foreign key.
     *
     * Nama constraint-nya dibaca dari information_schema, bukan ditebak
     * dari pola nama Laravel — pemasangan lama bisa saja punya nama lain,
     * dan menebak berarti migrasi ini gagal di tempat yang tidak terlihat.
     */
    private function pasangAturan(string $tabel, string $aturan): void
    {
        $nama = $this->namaConstraint($tabel);

        if ($nama === null) {
            return;
        }

        DB::statement("ALTER TABLE `{$tabel}` DROP FOREIGN KEY `{$nama}`");
        DB::statement(
            "ALTER TABLE `{$tabel}` ADD CONSTRAINT `{$nama}` "
            ."FOREIGN KEY (`tahun_ajaran_id`) REFERENCES `tahun_ajarans` (`id`) "
            ."ON DELETE {$aturan}"
        );
    }

    private function namaConstraint(string $tabel): ?string
    {
        $baris = DB::selectOne(
            'SELECT CONSTRAINT_NAME nama
               FROM information_schema.KEY_COLUMN_USAGE
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = "tahun_ajaran_id"
                AND REFERENCED_TABLE_NAME = "tahun_ajarans"
              LIMIT 1',
            [$tabel]
        );

        return $baris->nama ?? null;
    }
};
