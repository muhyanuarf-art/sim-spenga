<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            // Nomor WhatsApp orang tua/wali, format internasional tanpa "+"
            // (mis. 6281234567890) supaya langsung siap dipakai WhatsApp Cloud API.
            $table->string('no_hp_ortu')->nullable()->after('jenis_kelamin');
        });
    }

    public function down(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            $table->dropColumn('no_hp_ortu');
        });
    }
};
