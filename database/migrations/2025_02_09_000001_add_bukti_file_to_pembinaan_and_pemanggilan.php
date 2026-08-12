<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembinaan_siswas', function (Blueprint $table) {
            $table->string('bukti_file')->nullable()->after('hasil_pembinaan');
        });

        Schema::table('pemanggilan_orangtuas', function (Blueprint $table) {
            $table->string('bukti_file')->nullable()->after('hasil_pertemuan');
        });
    }

    public function down(): void
    {
        Schema::table('pembinaan_siswas', function (Blueprint $table) {
            $table->dropColumn('bukti_file');
        });

        Schema::table('pemanggilan_orangtuas', function (Blueprint $table) {
            $table->dropColumn('bukti_file');
        });
    }
};
