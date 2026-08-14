<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Base para endpoints de la aplicación móvil que requieren un paciente.
 */
abstract class PacienteAutenticadoController extends Controller
{
    /**
     * Obtiene el perfil de paciente asociado al usuario autenticado.
     *
     * Aunque las rutas están protegidas por Sanctum, el token puede pertenecer
     * a un usuario sin perfil de paciente.
     *
     * @param  Request  $request  Solicitud de la que se obtiene el usuario autenticado.
     * @return Paciente Perfil asociado al usuario autenticado.
     */
    protected function pacienteAutenticado(Request $request): Paciente
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->isA(User::ROL_PACIENTE), 403);

        $paciente = $user->paciente;

        abort_unless($paciente instanceof Paciente, 403);

        return $paciente;
    }
}
