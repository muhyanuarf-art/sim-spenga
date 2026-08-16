<?php

namespace App\Http\Middleware;

use App\Models\TahunAjaran;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePeriodeTidakTerkunci
{
    /**
     * Blokir aksi TULIS (POST/PUT/PATCH/DELETE) kalau tahun ajaran yang
     * sedang AKTIF berstatus terkunci. Route GET/lihat/cetak tidak pernah
     * dipasangi middleware ini sehingga tetap bebas diakses.
     *
     * Kalau tidak ada tahun ajaran aktif sama sekali, atau periode aktifnya
     * tidak terkunci, request diteruskan seperti biasa.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tahunAjaran = TahunAjaran::aktif();

        if ($tahunAjaran && $tahunAjaran->isTerkunci()) {
            return back()->with(
                'error',
                "Periode {$tahunAjaran->nama} {$tahunAjaran->semester} sedang dikunci. Data tidak dapat diubah. Hubungi Admin untuk membuka kunci."
            );
        }

        return $next($request);
    }
}
