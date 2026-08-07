<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWaliKelas
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ($user->role === 'admin' || $user->role === 'kurikulum' || $user->role === 'kepala_sekolah' || $user->isWaliKelas())) {
            return $next($request);
        }

        abort(403, 'Halaman khusus Wali Kelas.');
    }
}
