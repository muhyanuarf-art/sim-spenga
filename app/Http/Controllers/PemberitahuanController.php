<?php

namespace App\Http\Controllers;

use App\Models\Pemberitahuan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Lonceng pemberitahuan.
 *
 * Isi loncengnya sendiri dirender komponen <x-lonceng-pemberitahuan />
 * yang membaca langsung dari model — controller ini hanya menangani
 * tiga aksi: membuka satu pemberitahuan, menandai semuanya terbaca, dan
 * memberi angka terbaru untuk lonceng.
 */
class PemberitahuanController extends Controller
{
    /**
     * Buka satu pemberitahuan: tandai terbaca, lalu antar ke halamannya.
     *
     * Dibuat POST, bukan GET, karena aksi ini MENGUBAH keadaan. Dengan
     * GET, peramban yang mengambil pratinjau tautan bisa menandainya
     * terbaca tanpa ada orang yang membukanya.
     */
    public function buka(Request $request, Pemberitahuan $pemberitahuan): RedirectResponse
    {
        // Milik orang lain tidak boleh dibuka — dan tidak boleh pula
        // dibedakan dari yang tidak ada, supaya id-nya tidak bisa
        // ditelusuri satu per satu.
        abort_if($pemberitahuan->untuk_user_id !== $request->user()->id, 404);

        if (! $pemberitahuan->sudahDibaca()) {
            $pemberitahuan->update(['dibaca_at' => now()]);
        }

        return redirect($pemberitahuan->tautan ?: route('dashboard'));
    }

    public function bacaSemua(Request $request): RedirectResponse
    {
        Pemberitahuan::milik($request->user()->id)
            ->belumDibaca()
            ->update(['dibaca_at' => now()]);

        return back()->with('success', 'Semua pemberitahuan ditandai sudah dibaca.');
    }

    /**
     * Angka untuk lonceng, dipanggil berkala oleh halaman.
     *
     * Sengaja hanya mengembalikan ANGKA, bukan daftar isinya. Halaman
     * tidak perlu isinya sampai loncengnya diklik, dan alamat ini
     * dipanggil berulang — jadi jawabannya dibuat sekecil mungkin.
     */
    public function jumlah(Request $request): JsonResponse
    {
        return response()->json([
            'jumlah' => Pemberitahuan::milik($request->user()->id)->belumDibaca()->count(),
        ]);
    }
}
