<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * ALAT DIAGNOSTIK SEMENTARA — bukan bagian permanen aplikasi.
 *
 * Menampilkan badge kecil di pojok kiri bawah SETIAP halaman berisi:
 * jumlah query database, total waktu yang dihabiskan di database, dan
 * total waktu keseluruhan request (dari middleware ini mulai sampai
 * selesai).
 *
 * TIDAK AKTIF SAMA SEKALI kecuali:
 * 1. APP_DEBUG=true di .env (jangan pernah true di server produksi
 *    sungguhan — ini sudah standar Laravel, bukan aturan baru), DAN
 * 2. DEBUG_QUERY_BADGE=true ditambahkan di .env secara eksplisit.
 *
 * Tujuannya SATU: memastikan dengan ANGKA PASTI di mana waktu loading
 * sebenarnya habis (di database, atau di luar database) — supaya
 * langkah perbaikan berikutnya tepat sasaran, bukan tebak-tebakan lagi.
 *
 * Cara pakai: tambahkan baris "DEBUG_QUERY_BADGE=true" di .env, buka
 * halaman yang terasa lambat (Pantau Pelanggaran / Status WhatsApp
 * Ortu / Dashboard), baca badge di pojok kiri bawah, kirimkan
 * angkanya. Setelah selesai dipakai, hapus baris itu dari .env (atau
 * biarkan "false") supaya badge tidak muncul lagi.
 */
class QueryDebugBadge
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.debug') || ! filter_var(env('DEBUG_QUERY_BADGE', false), FILTER_VALIDATE_BOOL)) {
            return $next($request);
        }

        $waktuMulai = microtime(true);
        $queries = [];

        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->time; // waktu tiap query dalam milidetik (sudah dari Laravel)
        });

        $response = $next($request);

        $totalWaktuMs = round((microtime(true) - $waktuMulai) * 1000, 1);
        $jumlahQuery = count($queries);
        $waktuQueryMs = round(array_sum($queries), 1);
        $waktuLuarDbMs = round($totalWaktuMs - $waktuQueryMs, 1);

        $contentType = $response->headers->get('Content-Type', '');
        if (str_contains($contentType, 'text/html') && method_exists($response, 'setContent')) {
            $warna = $jumlahQuery > 30 ? '#dc2626' : ($jumlahQuery > 15 ? '#d97706' : '#16a34a');
            $badge = <<<HTML
            <div style="position:fixed;bottom:0;left:0;z-index:2147483647;background:#0f172a;color:#e2e8f0;
                        font:12px/1.5 monospace;padding:8px 14px;border-top-right-radius:10px;
                        box-shadow:0 -2px 12px rgba(0,0,0,.3);opacity:.95;pointer-events:none;">
                <span style="color:{$warna};font-weight:bold;">⚡ {$jumlahQuery} query</span>
                &nbsp;·&nbsp; {$waktuQueryMs}ms di database
                &nbsp;·&nbsp; {$waktuLuarDbMs}ms di luar database
                &nbsp;·&nbsp; <b>{$totalWaktuMs}ms total</b>
            </div>
            HTML;
            $response->setContent($response->getContent().$badge);
        }

        return $response;
    }
}
