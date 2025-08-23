<?php

namespace App\Http\Middleware;

use App\Models\Sppg;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SppgApiAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $code = $request->route('code'); // {code} di path
        $sppg = Sppg::where('code', $code)->first();

        if (!$sppg || !$sppg->is_active) {
            return response()->json(['error' => ['code' => 'FORBIDDEN', 'message' => 'SPPG tidak ditemukan / nonaktif']], 403);
        }

        // (opsional) IP whitelist
        if (is_array($sppg->ip_whitelist) && count($sppg->ip_whitelist) > 0) {
            if (!in_array($request->ip(), $sppg->ip_whitelist, true)) {
                return response()->json(['error' => ['code' => 'FORBIDDEN', 'message' => 'IP tidak diizinkan']], 403);
            }
        }

        // Authorization: Bearer <api_key>
        $apiKey = $request->bearerToken();
        if (!$apiKey || !hash_equals($sppg->api_key, $apiKey)) {
            return response()->json(['error' => ['code' => 'UNAUTHORIZED', 'message' => 'API key salah']], 401);
        }

        // simpan SPPG ke request untuk dipakai controller/rate limiter
        $request->attributes->set('sppg', $sppg);

        return $next($request);
    }
}
