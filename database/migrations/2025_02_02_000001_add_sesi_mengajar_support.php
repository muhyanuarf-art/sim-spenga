<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan dukungan "sesi mengajar" agar guru yang masuk beberapa jam
     * berurutan (mis. jam 1-3) untuk mapel & kelas yang sama cukup mengisi
     * Jurnal Mengajar + Absensi 1x saja, bukan berulang per jam pelajaran.
     *
     * - jurnal_mengajars.jam_pelajaran_id  = jam AWAL sesi (sudah ada sebelumnya)
     * - jurnal_mengajars.jam_pelajaran_id_akhir = jam AKHIR sesi (baru, nullable)
     *   Jika sama dengan jam_pelajaran_id / null, berarti sesi hanya 1 jam.
     * - jurnal_mengajar_slots = tabel penghubung 1 jurnal -> banyak jadwal_pelajaran,
     *   dengan unique(jadwal_pelajaran_id, tanggal) supaya 1 slot jadwal pada
     *   1 tanggal tidak mungkin "kepakai" oleh 2 jurnal berbeda.
     *
     * Catatan: setiap langkah dibungkus pengecekan "kalau belum ada" supaya
     * migration ini AMAN dijalankan ulang (idempotent) walau sempat gagal
     * di tengah jalan pada percobaan sebelumnya.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('jurnal_mengajars', 'jam_pelajaran_id_akhir')) {
            Schema::table('jurnal_mengajars', function (Blueprint $table) {
                $table->foreignId('jam_pelajaran_id_akhir')
                    ->nullable()
                    ->after('jam_pelajaran_id')
                    ->constrained('jam_pelajarans')
                    ->nullOnDelete();
            });
        }

        // MySQL/MariaDB tidak mengizinkan drop unique index selama index itu
        // masih dipakai untuk menopang foreign key constraint. Jadi urutannya:
        // 1) drop foreign key jadwal_pelajaran_id (jika masih ada),
        // 2) baru drop unique index-nya (jika masih ada),
        // 3) pasang lagi foreign key-nya (jika belum ada).
        if ($this->indexExists('jurnal_mengajars', 'jurnal_unique_slot_tanggal')) {
            $fkName = $this->foreignKeyName('jurnal_mengajars', 'jadwal_pelajaran_id');
            if ($fkName) {
                Schema::table('jurnal_mengajars', function (Blueprint $table) use ($fkName) {
                    $table->dropForeign($fkName);
                });
            }
            Schema::table('jurnal_mengajars', function (Blueprint $table) {
                $table->dropUnique('jurnal_unique_slot_tanggal');
            });
        }

        if (!$this->foreignKeyName('jurnal_mengajars', 'jadwal_pelajaran_id')) {
            Schema::table('jurnal_mengajars', function (Blueprint $table) {
                $table->foreign('jadwal_pelajaran_id')
                    ->references('id')->on('jadwal_pelajarans')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasTable('jurnal_mengajar_slots')) {
            Schema::create('jurnal_mengajar_slots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('jurnal_mengajar_id')->constrained('jurnal_mengajars')->cascadeOnDelete();
                $table->foreignId('jadwal_pelajaran_id')->constrained('jadwal_pelajarans')->cascadeOnDelete();
                $table->date('tanggal');
                $table->timestamps();

                // Kunci utama pencegah "tabrakan": 1 slot jadwal pada 1 tanggal
                // hanya boleh dipakai oleh 1 jurnal mengajar (baik sesi 1 jam
                // maupun bagian dari sesi beberapa jam).
                $table->unique(['jadwal_pelajaran_id', 'tanggal'], 'slot_tanggal_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('jurnal_mengajar_slots');

        if (Schema::hasColumn('jurnal_mengajars', 'jam_pelajaran_id_akhir')) {
            $fkAkhir = $this->foreignKeyName('jurnal_mengajars', 'jam_pelajaran_id_akhir');
            Schema::table('jurnal_mengajars', function (Blueprint $table) use ($fkAkhir) {
                if ($fkAkhir) {
                    $table->dropForeign($fkAkhir);
                }
                $table->dropColumn('jam_pelajaran_id_akhir');
            });
        }

        $fkJadwal = $this->foreignKeyName('jurnal_mengajars', 'jadwal_pelajaran_id');
        if ($fkJadwal) {
            Schema::table('jurnal_mengajars', function (Blueprint $table) use ($fkJadwal) {
                $table->dropForeign($fkJadwal);
            });
        }
        if (!$this->indexExists('jurnal_mengajars', 'jurnal_unique_slot_tanggal')) {
            Schema::table('jurnal_mengajars', function (Blueprint $table) {
                $table->unique(['jadwal_pelajaran_id', 'tanggal'], 'jurnal_unique_slot_tanggal');
            });
        }
        Schema::table('jurnal_mengajars', function (Blueprint $table) {
            $table->foreign('jadwal_pelajaran_id')
                ->references('id')->on('jadwal_pelajarans')
                ->nullOnDelete();
        });
    }

    /** Cek apakah suatu index (nama tertentu) ada di tabel, lewat information_schema. */
    private function indexExists(string $table, string $indexName): bool
    {
        $db = DB::getDatabaseName();
        $rows = DB::select(
            'SELECT INDEX_NAME FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$db, $table, $indexName]
        );
        return count($rows) > 0;
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
