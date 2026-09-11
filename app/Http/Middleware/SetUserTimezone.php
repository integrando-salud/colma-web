<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\UserTimezone;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetUserTimezone
{
    /**
     * Publica el huso del usuario para la capa de presentación.
     *
     * No se modifica config('app.timezone') ni el timezone de PHP: ambos se
     * mantienen en UTC para que Eloquent lea y escriba los DATETIME como UTC.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            $user->loadMissing('pais');
            $request->attributes->set(UserTimezone::REQUEST_ATTRIBUTE, UserTimezone::forUser($user));
        }

        return $next($request);
    }
}
