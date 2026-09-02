<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

class UserTimezone
{
    public const string ARGENTINA = 'America/Argentina/Buenos_Aires';

    public const string PERU = 'America/Lima';

    public const string REQUEST_ATTRIBUTE = 'user_timezone';

    /**
     * El país es la fuente de verdad del huso mientras no exista una
     * preferencia individual de zona horaria.
     */
    public static function forUser(?User $user): string
    {
        return match ($user?->pais?->codigo) {
            'PE' => self::PERU,
            'AR', null => self::ARGENTINA,
            default => self::ARGENTINA,
        };
    }

    /** Obtiene el huso de visualización de la petición actual. */
    public static function forRequest(Request $request): string
    {
        return $request->attributes->get(
            self::REQUEST_ATTRIBUTE,
            $request->user() instanceof User ? self::forUser($request->user()) : config('app.timezone')
        );
    }
}
