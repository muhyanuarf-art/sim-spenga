<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * kasus_siswas, pembinaan_siswas, pengurangan_poin_siswas,
     * pemanggilan_orangtuas, dan jurnal_mengajars didesain "TIDAK PERNAH
     * dihapus" (prinsip Bagian 29 spec) — koreksi dilakukan lewat
     * dibatalkan_at, bukan delete. Tapi kolom guru_pelapor_id / petugas_id /
     * guru_id di tabel-tabel itu masih cascadeOnDelete() ke users, jadi
     * kalau akun Guru/BK yang bersangkutan dihapus, seluruh riwayat yang
     * pernah dia input ikut terhapus permanen — bertentangan dengan
     * prinsip "tidak pernah dihapus" itu sendiri.
     *
     * Migration ini mengubah kolom-kolom tsb jadi nullable +
     * nullOnDelete(), supaya hapus akun user hanya melepas kaitan
     * "siapa yang input", bukan menghapus riwayatnya.
     */
    private array $kolom = [
        'kasus_siswas' => 'guru_pelapor_id',
        'pembinaan_siswas' => 'petugas_id',
        'pengurangan_poin_siswas' => 'petugas_id',
        'pemanggilan_orangtuas' => 'petugas_id',
        'jurnal_mengajars' => 'guru_id',
    ];

    public function up(): void
    {
        foreach ($this->kolom as $table => $column) {
            $fkName = $this->foreignKeyName($table, $column);
            if ($fkName) {
                Schema::table($table, function (Blueprint $t) use ($fkName) {
                    $t->dropForeign($fkName);
                });
            }

            if (! $this->isNullable($table, $column)) {
                Schema::table($table, function (Blueprint $t) use ($column) {
                    $t->unsignedBigInteger($column)->nullable()->change();
                });
            }

            Schema::table($table, function (Blueprint $t) use ($column) {
                $t->foreign($column)->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->kolom as $table => $column) {
            $fkName = $this->foreignKeyName($table, $column);
            if ($fkName) {
                Schema::table($table, function (Blueprint $t) use ($fkName) {
                    $t->dropForeign($fkName);
                });
            }

            Schema::table($table, function (Blueprint $t) use ($column) {
                $t->foreign($column)->references('id')->on('users')->cascadeOnDelete();
            });
        }
    }

    private function isNullable(string $table, string $column): bool
    {
        $db = DB::getDatabaseName();
        $rows = DB::select(
            'SELECT IS_NULLABLE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$db, $table, $column]
        );
        return isset($rows[0]) && $rows[0]->IS_NULLABLE === 'YES';
    }

    /** Ambil nama constraint foreign key pada kolom tertentu (null kalau tidak ada). */
    private function foreignKeyName(string $table, string $column): ?string
    {
        $db = DB::getDatabaseName();
        $rows = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$db, $table, $column]
        );
        return $rows[0]->CONSTRAINT_NAME ?? null;
    }
};
