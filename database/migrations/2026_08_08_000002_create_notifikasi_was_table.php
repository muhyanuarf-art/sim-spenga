<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi_was', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->date('tanggal');

            // menunggu -> job masih di queue / belum diproses worker
            // terkirim -> sudah diterima server WhatsApp Cloud API (atau webhook status "sent")
            // diterima -> webhook status "delivered" (sampai ke HP tujuan)
            // dibaca   -> webhook status "read"
            // gagal    -> webhook status "failed", atau job gagal kirim setelah percobaan habis
            $table->enum('status', ['menunggu', 'terkirim', 'diterima', 'dibaca', 'gagal'])
                ->default('menunggu');

            // Berapa kali sudah dicoba kirim (maks 2). Percobaan ke-2 hanya
            // dipicu kalau percobaan sebelumnya berstatus gagal.
            $table->unsignedTinyInteger('percobaan_ke')->default(1);

            // ID pesan dari WhatsApp Cloud API (wamid...), dipakai untuk
            // mencocokkan callback status yang masuk lewat webhook.
            $table->string('wa_message_id')->nullable()->index();

            $table->string('no_hp_tujuan')->nullable();
            $table->text('pesan')->nullable();
            $table->text('keterangan_gagal')->nullable();

            $table->timestamp('terkirim_at')->nullable();
            $table->timestamp('diterima_at')->nullable();
            $table->timestamp('dibaca_at')->nullable();
            $table->timestamp('gagal_at')->nullable();

            $table->timestamps();

            // Kunci utama pencegah duplikat: 1 siswa hanya boleh punya 1 baris
            // notifikasi per tanggal, walau ada 2 request submit absensi yang
            // hampir bersamaan (dijamin di level database, bukan cuma query cek).
            $table->unique(['siswa_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi_was');
    }
};
