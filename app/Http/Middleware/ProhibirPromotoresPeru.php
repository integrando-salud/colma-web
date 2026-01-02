<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProhibirPromotoresPeru
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user->roles->first()->name === 'promotor' && $user->pais?->id === 2) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Permiso denegado.'], 403);
            }

            abort(403, 'Permiso denegado.');
        }

        return $next($request);
    }
}
