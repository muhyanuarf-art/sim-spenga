<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;

abstract class Controller
{
    /**
     * Hapus sebuah model, tapi tangkap error constraint FK (mis. data masih
     * dipakai relasi lain) supaya user melihat pesan error yang ramah,
     * bukan error 500 mentah.
     *
     * (2026-08-28) Pesan penolakannya kini DILENGKAPI rincian apa saja yang
     * memakai baris itu — "masih dipakai 12 jadwal pelajaran dan 340 nilai
     * siswa" — supaya operator tahu apa yang harus dibereskan lebih dulu,
     * bukan sekadar diberi tahu bahwa tidak bisa.
     *
     * Rinciannya dihitung SEBELUM delete: setelah query gagal, transaksinya
     * sudah tidak bisa dipakai untuk menghitung apa pun.
     */
    protected function hapusAtauGagalDenganPesan(\Illuminate\Database\Eloquent\Model $model, string $pesanBerhasil, string $pesanMasihDipakai): RedirectResponse
    {
        $pemakai = \App\Support\PemakaiData::kalimat($model);

        try {
            $model->delete();
        } catch (QueryException $e) {
            if ((int) $e->getCode() === 23000) {
                return back()->with(
                    'error',
                    $pemakai
                        ? rtrim($pesanMasihDipakai, '.').' — masih dipakai '.$pemakai.'.'
                        : $pesanMasihDipakai
                );
            }
            throw $e;
        }

        return back()->with('success', $pesanBerhasil);
    }
}
