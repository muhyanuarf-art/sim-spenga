<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifikasi_alfa_terkirims', function (Blueprint $table) {
            $table->foreignId('mata_pelajaran_id')->nullable()->after('tanggal')
                ->constrained('mata_pelajarans')->nullOnDelete();
            $table->unsignedInteger('jam_ke')->nullable()->after('mata_pelajaran_id');
        });
    }

    public function down(): void
    {
        Schema::table('notifikasi_alfa_terkirims', function (Blueprint $table) {
            $table->dropForeign(['mata_pelajaran_id']);
            $table->dropColumn(['mata_pelajaran_id', 'jam_ke']);
        });
    }
};
