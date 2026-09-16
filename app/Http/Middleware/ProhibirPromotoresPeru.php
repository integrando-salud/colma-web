<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProhibirPromotoresPeru
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // El mismo endpoint se utiliza para altas y ediciones. Los promotores de
        // Perú sólo tienen restringida el alta; las ediciones quedan sujetas a la
        // política del paciente, que valida que el paciente les pertenezca.
        if ($user->isA(User::ROL_PROMOTOR)
            && $user->pais?->codigo === 'PE'
            && ! $request->filled('id')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Permiso denegado.'], 403);
            }

            abort(403, 'Permiso denegado.');
        }

        return $next($request);
    }
}
