<?php

namespace App\Http\Controllers;

use App\Support\Lisensi;
use Illuminate\Http\Request;

/**
 * Halaman aktivasi: satu-satunya halaman yang terbuka sebelum aplikasi
 * dilisensikan. Lihat App\Support\Lisensi.
 */
class AktivasiController extends Controller
{
    public function form()
    {
        if (Lisensi::aktif()) {
            return redirect()->route('login');
        }

        return view('aktivasi.form', [
            'pemegang' => config('lisensi.pemegang'),
            'host' => Lisensi::host(),
            'terikatHost' => (bool) config('lisensi.terikat_host', true),
        ]);
    }

    public function simpan(Request $request)
    {
        $validated = $request->validate([
            'nomor_seri' => ['required', 'string', 'max:60'],
        ], [], ['nomor_seri' => 'Nomor Seri']);

        if (! Lisensi::aktifkan($validated['nomor_seri'], $request->ip())) {
            return back()->withErrors([
                'nomor_seri' => 'Nomor seri tidak dikenali. Periksa kembali penulisannya — huruf besar/kecil dan tanda hubung tidak masalah.',
            ]);
        }

        return redirect()->route('login')->with(
            'success',
            'Aplikasi berhasil diaktifkan untuk '.config('lisensi.pemegang').'. Silakan masuk.'
        );
    }
}
