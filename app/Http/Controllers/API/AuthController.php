<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Auth\LoginRequest;
use App\Http\Resources\API\InfoUsuarioResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 5;

    private const LOGIN_DECAY_SECONDS = 60;

    /**
     * Autentica a un paciente en la aplicación móvil y emite un token Sanctum.
     *
     * Las credenciales inválidas, los usuarios que no son pacientes y los
     * pacientes sin perfil asociado devuelven la misma respuesta para no
     * revelar información sobre las cuentas. El primer acceso se registra una
     * única vez mediante una actualización condicional, incluso si llegan dos
     * solicitudes simultáneas.
     *
     * @param  LoginRequest  $request  Solicitud con documento y contraseña validados.
     * @return JsonResponse Respuesta con el token y los datos del paciente, o un error de autenticación.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $throttleKey = $this->throttleKey($credentials['documento'], $request);

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_LOGIN_ATTEMPTS)) {
            return response()->json([
                'message' => $this->throttleErrorMessage($throttleKey),
            ], 429);
        }

        $user = User::query()
            ->with(['paciente', 'pais', 'centroSalud'])
            ->where('documento', $credentials['documento'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password) || $user->isNotA(User::ROL_PACIENTE) || ! $user->paciente) {
            RateLimiter::hit($throttleKey, self::LOGIN_DECAY_SECONDS);

            return response()->json([
                'message' => 'Documento o contraseña incorrectos',
            ], 401);
        }

        if (! $user->paciente->fecha_primer_login_app) {
            $user->paciente()
                ->whereNull('fecha_primer_login_app')
                ->update([
                    'fecha_primer_login_app' => now(),
                ]);

            $user->load('paciente');
        }

        RateLimiter::clear($throttleKey);

        return response()->json([
            'token' => $user->createToken('mobile-app')->plainTextToken,
            'usuario' => new InfoUsuarioResource($user),
        ]);
    }

    /**
     * Genera la clave que identifica los intentos de una cuenta desde una IP.
     *
     * @param  string  $documento  Documento suministrado en la solicitud.
     * @param  LoginRequest  $request  Solicitud de la que se obtiene la dirección IP.
     * @return string Clave única para el limitador de intentos.
     */
    private function throttleKey(string $documento, LoginRequest $request): string
    {
        return Str::lower($documento).'|'.$request->ip();
    }

    /**
     * Construye el mensaje de bloqueo con el tiempo de espera restante.
     *
     * @param  string  $throttleKey  Clave previamente registrada en el limitador.
     * @return string Mensaje seguro para el cliente de la API.
     */
    private function throttleErrorMessage(string $throttleKey): string
    {
        return sprintf(
            'Demasiados intentos de inicio de sesión. Intente nuevamente en %d segundos.',
            RateLimiter::availableIn($throttleKey),
        );
    }
}
