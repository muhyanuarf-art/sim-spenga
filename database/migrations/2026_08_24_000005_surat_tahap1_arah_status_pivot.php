<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Modul Surat — Tahap 1: extend schema untuk arah surat, status,
 * tahun ajaran, dan relasi banyak-siswa (pivot).
 *
 * Kolom `surats.siswa_id` TETAP ADA (tidak dihapus) — backward compat.
 * Data lama disalin ke pivot `surat_siswa`. Kode baru pakai pivot;
 * `siswa_id` jadi "siswa utama" saja untuk tampilan ringkas.
 *
 * Default status surat LAMA = 'selesai' (mereka sudah final/tercetak
 * sebelum fitur status ada). Default arah = 'keluar' (semua surat lama
 * dibuat sekolah, bukan surat masuk).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jenis_surats', function (Blueprint $table) {
            $table->string('kode')->nullable()->after('nama_jenis');
            $table->enum('kategori', ['masuk', 'keluar', 'internal'])->default('keluar')->after('kode');
            $table->boolean('is_aktif')->default(true)->after('template_isi');
        });

        Schema::table('surats', function (Blueprint $table) {
            $table->enum('arah', ['masuk', 'keluar', 'internal'])->default('keluar')->after('jenis_surat_id');
            $table->enum('status', [
                'draft', 'menunggu_persetujuan', 'aktif', 'diproses', 'selesai', 'diarsipkan', 'dibatalkan',
            ])->default('draft')->after('arah');
            $table->string('sifat')->nullable()->after('status'); // biasa/penting/segera/rahasia — teks bebas
            $table->string('asal_surat')->nullable()->after('sifat');
            $table->string('tujuan_surat')->nullable()->after('asal_surat');
            $table->date('tanggal_diterima')->nullable()->after('tanggal');
            $table->foreignId('tahun_ajaran_id')->nullable()->after('siswa_id')->constrained('tahun_ajarans');
        });

        // Surat LAMA (dibuat sebelum tahap ini) dianggap sudah final.
        DB::table('surats')->update(['status' => 'selesai']);

        Schema::create('surat_siswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_id')->constrained('surats')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswas');
            $table->timestamps();
            $table->unique(['surat_id', 'siswa_id']);
        });

        // Backfill: siswa_id existing -> pivot.
        DB::table('surats')->whereNotNull('siswa_id')->orderBy('id')->each(function ($row) {
            DB::table('surat_siswa')->insert([
                'surat_id' => $row->id,
                'siswa_id' => $row->siswa_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_siswa');

        Schema::table('surats', function (Blueprint $table) {
            $table->dropForeign(['tahun_ajaran_id']);
            $table->dropColumn(['arah', 'status', 'sifat', 'asal_surat', 'tujuan_surat', 'tanggal_diterima', 'tahun_ajaran_id']);
        });

        Schema::table('jenis_surats', function (Blueprint $table) {
            $table->dropColumn(['kode', 'kategori', 'is_aktif']);
        });
    }
};
