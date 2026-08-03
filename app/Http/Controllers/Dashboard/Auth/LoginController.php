<?php

namespace App\Http\Controllers\Dashboard\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 5;

    private const LOGIN_DECAY_SECONDS = 60;

    /**
     * Muestra el formulario de acceso al panel administrativo.
     *
     * @return Response Vista Inertia del formulario de inicio de sesión.
     */
    public function index(): Response
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * Autentica a un usuario habilitado para el panel administrativo.
     *
     * Los usuarios con el rol de paciente solo pueden ingresar mediante la
     * aplicación móvil, por lo que reciben el mismo error que unas
     * credenciales inválidas. Tras autenticarse se regenera el identificador
     * de sesión para prevenir fijación de sesión y se registra el acceso.
     *
     * @param  LoginRequest  $request  Credenciales ya validadas.
     * @return RedirectResponse Redirección al destino previsto o al panel; ante
     *                          un fallo, vuelve al formulario con un error.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();
        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_LOGIN_ATTEMPTS)) {
            return back()->withErrors([
                'login' => $this->throttleErrorMessage($throttleKey),
            ])->onlyInput('documento');
        }

        $user = User::where('documento', $credentials['documento'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password) || $user->isA(User::ROL_PACIENTE)) {
            RateLimiter::hit($throttleKey, self::LOGIN_DECAY_SECONDS);

            return back()->withErrors([
                'login' => 'Documento o contraseña incorrectos.',
            ])->onlyInput('documento');
        }

        Auth::login($user);
        $request->session()->regenerate();
        RateLimiter::clear($throttleKey);
        $user->increment('accesos', 1, ['ultimo_acceso' => now()]);

        return redirect()->intended(route('dashboard.index'));
    }

    /**
     * Cierra la sesión actual e invalida sus datos y token CSRF.
     *
     * @param  Request  $request  Solicitud que contiene la sesión a invalidar.
     * @return RedirectResponse Redirección al formulario de inicio de sesión.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect(route('dashboard.login'));
    }

    /**
     * Genera la clave del límite de intentos para un documento y una IP.
     *
     * Incluir ambos valores evita que los intentos contra una cuenta afecten a
     * usuarios que comparten red y dificulta ataques repetidos desde una misma
     * dirección.
     *
     * @param  Request  $request  Solicitud cuyo documento e IP se identificarán.
     * @return string Clave única usada por el limitador de intentos.
     */
    private function throttleKey(Request $request): string
    {
        return Str::lower((string) $request->input('documento')).'|'.$request->ip();
    }

    /**
     * Construye el mensaje de bloqueo con el tiempo restante de espera.
     *
     * @param  string  $throttleKey  Clave previamente registrada en el limitador.
     * @return string Mensaje de error seguro para mostrar en el formulario.
     */
    private function throttleErrorMessage(string $throttleKey): string
    {
        return sprintf(
            'Demasiados intentos de inicio de sesión. Intente nuevamente en %d segundos.',
            RateLimiter::availableIn($throttleKey),
        );
    }
}
