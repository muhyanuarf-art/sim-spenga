<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kasus_siswas', function (Blueprint $table) {
            $table->string('bukti_file')->nullable()->after('bukti_catatan');
        });
    }

    public function down(): void
    {
        Schema::table('kasus_siswas', function (Blueprint $table) {
            $table->dropColumn('bukti_file');
        });
    }
};
