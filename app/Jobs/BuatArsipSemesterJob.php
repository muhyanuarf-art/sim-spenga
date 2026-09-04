<?php

namespace App\Jobs;

use App\Models\ArsipSemester;
use App\Support\PembuatArsip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Membuat arsip semester di latar belakang.
 *
 * WAJIB lewat antrian, bukan langsung di permintaan Admin: merender
 * puluhan laporan menjadi PDF memakan menit, jauh melewati batas waktu
 * eksekusi PHP di hosting bersama. Admin menekan tombol, halaman
 * langsung kembali, dan berkasnya menyusul.
 */
class BuatArsipSemesterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Sekali saja. Gagal di tengah menyisakan status 'gagal' yang terbaca Admin. */
    public int $tries = 1;

    /** Sekolah besar dengan banyak kelas butuh waktu; 15 menit sudah lapang. */
    public int $timeout = 900;

    public function __construct(public int $arsipId)
    {
        $this->onQueue('arsip');
    }

    public function handle(): void
    {
        $arsip = ArsipSemester::with(['tahunAjaran', 'pembuat'])->find($this->arsipId);

        if (! $arsip || ! $arsip->tahunAjaran || ! $arsip->pembuat) {
            return;
        }

        (new PembuatArsip($arsip->tahunAjaran, $arsip->pembuat))->jalankan($arsip);
    }

    public function failed(\Throwable $e): void
    {
        ArsipSemester::where('id', $this->arsipId)->update([
            'status' => 'gagal',
            'catatan' => \Illuminate\Support\Str::limit($e->getMessage(), 400),
            'selesai_at' => now(),
        ]);
    }
}
