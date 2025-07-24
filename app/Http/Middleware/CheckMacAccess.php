<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cookie;
use App\Models\MacAddress;

class CheckMacAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        $mac = $request->cookie('mac_address');

        if ($mac) {
            $macEntry = MacAddress::where('mac', $mac)->first();

            if (!$macEntry) {
                // Tidak terdaftar, insert baru dengan status 0 (pending)
                MacAddress::create([
                    'mac' => $mac,
                    'status' => 0
                ]);
                return response('Perangkat pending approval.', 403);
            }

            if ($macEntry->status == 1) {
                $response = $next($request);
                $response->headers->setCookie(cookie('mac_address', $mac, 60 * 24 * 30));
                return $response;
            }

            if ($macEntry->status == 2) {
                return response('Perangkat ditolak.', 403);
            }

            return response('Perangkat pending approval.', 403);
        }

        return response('Perangkat tidak terdaftar.', 403);
    }
}
