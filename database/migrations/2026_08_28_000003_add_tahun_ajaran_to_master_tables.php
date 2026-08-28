<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MASTER DATA IKUT TAHUN AJARAN (permintaan admin, 28 Agustus 2026).
 *
 * =====================================================================
 * LATAR BELAKANG
 * =====================================================================
 * Sampai sekarang lima tabel master berlaku LINTAS TAHUN: mata pelajaran,
 * jam pelajaran, jenis pelanggaran, jenis surat, dan ekstrakurikuler.
 * Akibatnya, saat tahun ajaran berganti, isinya terbawa apa adanya —
 * termasuk hal yang seharusnya berhenti (mis. daftar anggota Pramuka
 * masih memuat siswa yang sudah lulus, ekstrakurikuler yang sudah tidak
 * dibuka lagi tetap muncul di menu absensi).
 *
 * Sekolah menghendaki: SATU periode = SATU kumpulan data. Begitu tahun
 * ajaran baru diaktifkan, seluruh menu mengikuti data periode itu saja.
 * Isinya diisi ulang dari awal, atau — supaya tidak melelahkan — DISALIN
 * dari periode sebelumnya lewat tombol "Salin Data" di menu Tahun Ajaran
 * (lihat TahunAjaranController::resolveRencanaSalin()).
 *
 * =====================================================================
 * KONVENSI tahun_ajaran_id — SAMA PERSIS DENGAN TABEL kelas
 * =====================================================================
 * Nilainya SELALU menunjuk baris SEMESTER GANJIL dari tahun ajaran yang
 * bersangkutan, karena master data berlaku untuk satu TAHUN penuh (Ganjil
 * dan Genap sekaligus), bukan per semester. Semua pembacaan lewat scope
 * bersama App\Models\Concerns\MilikTahunAjaran, yang otomatis mencarikan
 * baris Ganjil-nya bila yang aktif sedang Semester Genap.
 *
 * =====================================================================
 * YANG SENGAJA TIDAK IKUT DIUBAH
 * =====================================================================
 * - users               : akun & kata sandi. Kalau ikut periode, guru tidak
 *                         bisa login lagi setiap ganti tahun, dan seluruh
 *                         jejak lama (pelapor kasus, pencatat absensi)
 *                         menunjuk baris yang berbeda. Penugasan guru
 *                         SUDAH per periode lewat guru_mengajar_kelas,
 *                         guru_bk_kelas, dan kelas.wali_kelas_id.
 * - pengaturan_sekolahs : identitas sekolah (nama, alamat, logo, kop
 *                         surat). Kalau ikut periode, semua cetakan
 *                         kehilangan kop suratnya begitu tahun baru
 *                         diaktifkan sampai diisi ulang.
 * - siswas              : identitas orang, bukan kejadian. Siswa sudah
 *                         terikat periode LEWAT kelasnya (kelas.tahun_
 *                         ajaran_id), jadi siswa yang tidak diikutkan ke
 *                         kelas periode baru otomatis tidak muncul lagi —
 *                         tanpa perlu dinonaktifkan manual. Lihat
 *                         Siswa::scopeUntukTahunAjaran().
 * - ekstrakurikuler_siswas / ekstrakurikuler_pembinas / absensi_ekskuls :
 *                         ketiganya menunjuk ekstrakurikuler_id, dan
 *                         ekstrakurikulernya kini sudah per periode. Jadi
 *                         ketiganya ikut terpisah dengan sendirinya —
 *                         menambah kolom lagi hanya akan membuat dua
 *                         sumber kebenaran yang bisa saling bertentangan.
 *
 * =====================================================================
 * DATA LAMA
 * =====================================================================
 * Seluruh baris yang sudah ada diisi dengan tahun ajaran AKTIF saat
 * migrasi dijalankan (baris Semester Ganjil-nya). Tidak ada baris yang
 * dihapus atau diubah isinya. Kalau belum ada tahun ajaran sama sekali,
 * kolomnya dibiarkan NULL dan scope memperlakukannya sebagai "belum
 * bertuan" — tetap terbaca, supaya instalasi baru tidak jadi kosong.
 */
return new class extends Migration
{
    /**
     * Tabel yang diubah => indeks unik lamanya yang harus dijadikan
     * gabungan dengan tahun_ajaran_id. Tanpa ini, menyalin master data
     * ke periode baru akan gagal karena kode/nama-nya dianggap kembar.
     */
    private const TABEL = [
        'mata_pelajarans' => [
            'buang' => ['mata_pelajarans_kode_unique'],
            'unik' => [['tahun_ajaran_id', 'kode'], 'mapel_unik_periode_kode'],
        ],
        'jam_pelajarans' => [
            'buang' => ['jam_pelajarans_hari_jam_ke_unique'],
            'unik' => [['tahun_ajaran_id', 'hari', 'jam_ke'], 'jam_unik_periode_hari_jamke'],
        ],
        'jenis_pelanggarans' => [
            'buang' => ['jenis_pelanggarans_kode_unique'],
            'unik' => [['tahun_ajaran_id', 'kode'], 'pelanggaran_unik_periode_kode'],
        ],
        'jenis_surats' => [
            'buang' => [],
            'unik' => null,
        ],
        'ekstrakurikulers' => [
            'buang' => [],
            'unik' => null,
        ],
    ];

    public function up(): void
    {
        // Baris Semester Ganjil dari tahun ajaran yang sedang aktif.
        $periodeId = $this->idPeriodeAwal();

        foreach (self::TABEL as $tabel => $aturan) {
            if (! Schema::hasTable($tabel)) {
                continue;
            }

            if (! Schema::hasColumn($tabel, 'tahun_ajaran_id')) {
                Schema::table($tabel, function (Blueprint $t) {
                    $t->foreignId('tahun_ajaran_id')->nullable()->after('id')
                        ->constrained('tahun_ajarans')->nullOnDelete();
                });
            }

            // Isi data lama dengan periode aktif.
            if ($periodeId) {
                DB::table($tabel)->whereNull('tahun_ajaran_id')->update(['tahun_ajaran_id' => $periodeId]);
            }

            foreach ($aturan['buang'] as $indeks) {
                $this->buangIndeks($tabel, $indeks);
            }

            if ($aturan['unik']) {
                [$kolom, $nama] = $aturan['unik'];
                $this->buatIndeksUnik($tabel, $kolom, $nama);
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::TABEL) as $tabel => $aturan) {
            if (! Schema::hasTable($tabel) || ! Schema::hasColumn($tabel, 'tahun_ajaran_id')) {
                continue;
            }

            if ($aturan['unik']) {
                $this->buangIndeks($tabel, $aturan['unik'][1]);
            }

            Schema::table($tabel, function (Blueprint $t) use ($tabel) {
                $t->dropConstrainedForeignId('tahun_ajaran_id');

                // Kembalikan indeks unik aslinya.
                match ($tabel) {
                    'mata_pelajarans' => $t->unique('kode'),
                    'jenis_pelanggarans' => $t->unique('kode'),
                    'jam_pelajarans' => $t->unique(['hari', 'jam_ke']),
                    default => null,
                };
            });
        }
    }

    /** Baris Semester Ganjil dari tahun ajaran aktif (fallback: tahun ajaran mana pun yang ada). */
    private function idPeriodeAwal(): ?int
    {
        $aktif = DB::table('tahun_ajarans')->where('is_active', true)->first();

        if (! $aktif) {
            $aktif = DB::table('tahun_ajarans')->orderBy('id')->first();
        }

        if (! $aktif) {
            return null;
        }

        $ganjil = DB::table('tahun_ajarans')
            ->where('nama', $aktif->nama)->where('semester', 'Ganjil')->first();

        return (int) ($ganjil->id ?? $aktif->id);
    }

    private function buangIndeks(string $tabel, string $nama): void
    {
        $ada = collect(DB::select("SHOW INDEX FROM `{$tabel}`"))->contains(fn ($i) => $i->Key_name === $nama);

        if ($ada) {
            DB::statement("ALTER TABLE `{$tabel}` DROP INDEX `{$nama}`");
        }
    }

    private function buatIndeksUnik(string $tabel, array $kolom, string $nama): void
    {
        $ada = collect(DB::select("SHOW INDEX FROM `{$tabel}`"))->contains(fn ($i) => $i->Key_name === $nama);

        if ($ada) {
            return;
        }

        $daftar = collect($kolom)->map(fn ($k) => "`{$k}`")->implode(', ');
        DB::statement("ALTER TABLE `{$tabel}` ADD UNIQUE `{$nama}` ({$daftar})");
    }
};
