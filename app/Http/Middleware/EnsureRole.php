<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Usage: ->middleware('role:admin,kurikulum')
     * Admin selalu diloloskan (dapat melihat semuanya tanpa terkecuali).
     *
     * Yang dibandingkan adalah peranAkses(), bukan `role` mentah, supaya
     * peran yang haknya menumpang pada peran lain — Wakil Kepala Sekolah
     * pada Kepala Sekolah — ikut lolos tanpa perlu disebutkan satu per
     * satu di belasan baris `role:` di routes/web.php. Lihat
     * App\Models\User::PERAN_SETARA.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $peran = $user->peranAkses();

        if ($peran === 'admin') {
            return $next($request);
        }

        if (! in_array($peran, $roles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
