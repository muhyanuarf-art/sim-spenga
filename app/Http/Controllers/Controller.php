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
     */
    protected function hapusAtauGagalDenganPesan(\Illuminate\Database\Eloquent\Model $model, string $pesanBerhasil, string $pesanMasihDipakai): RedirectResponse
    {
        try {
            $model->delete();
        } catch (QueryException $e) {
            if ((int) $e->getCode() === 23000) {
                return back()->with('error', $pesanMasihDipakai);
            }
            throw $e;
        }

        return back()->with('success', $pesanBerhasil);
    }
}
