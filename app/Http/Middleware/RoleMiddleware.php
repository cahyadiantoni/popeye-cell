<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = auth()->user();

        if (!$user) {
            abort(403, 'Unauthorized');
        }

        if ($role === 'sales' && !$user->sales) {
            abort(403, 'Access denied');
        }

        if ($role === 'adm' && !$user->adm) {
            abort(403, 'Access denied');
        }

        return $next($request);
    }
}
