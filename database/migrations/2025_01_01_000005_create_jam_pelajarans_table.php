<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jam ke-1 s.d ke-10 (fleksibel, waktu diatur oleh admin, berlaku global)
        Schema::create('jam_pelajarans', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('jam_ke'); // 1..10
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['jam_ke']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jam_pelajarans');
    }
};
