<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use App\Models\User;
use App\Services\MultimediaService;
use App\Services\TratamientoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Expone el tratamiento y su material multimedia a usuarios de la aplicación
 * móvil que poseen un perfil de paciente.
 */
class TratamientoController extends Controller
{
    /**
     * Obtiene la estructura del tratamiento asignado al paciente autenticado.
     *
     * El paciente se determina exclusivamente a partir de la sesión para evitar
     * que un usuario autenticado consulte el tratamiento de otra persona. Un
     * paciente sin diagnóstico con tratamiento configurado recibe `null`.
     *
     * @param  Request  $request  Solicitud autenticada de la aplicación móvil.
     * @param  TratamientoService  $tratamientoService  Servicio que construye el tratamiento y sus recursos.
     * @return JsonResponse Respuesta JSON con el tratamiento o `null` si no corresponde uno.
     */
    public function getTratamiento(Request $request, TratamientoService $tratamientoService): JsonResponse
    {
        $tratamiento = $tratamientoService->getTratamientoPaciente($this->pacienteAutenticado($request));

        return response()->json($tratamiento);
    }

    /**
     * Descarga un archivo multimedia solicitado desde el tratamiento.
     *
     * El acceso se limita al material asignado al paciente autenticado. Luego,
     * el servicio valida la ruta, comprueba que el archivo exista y lo envía.
     *
     * @param  Request  $request  Solicitud autenticada de la aplicación móvil.
     * @param  string  $path  Ruta relativa del archivo multimedia solicitada en la URL.
     * @param  TratamientoService  $tratamientoService  Servicio que autoriza el material para el paciente.
     * @param  MultimediaService  $multimediaService  Servicio que valida y devuelve el archivo.
     * @return BinaryFileResponse Respuesta de descarga del archivo multimedia.
     */
    public function getMultimedia(
        Request $request,
        string $path,
        TratamientoService $tratamientoService,
        MultimediaService $multimediaService,
    ): BinaryFileResponse
    {
        abort_unless(
            $tratamientoService->puedeAccederAMultimedia($this->pacienteAutenticado($request), $path),
            404,
        );

        return $multimediaService->descargarMultimedia($path);
    }

    /**
     * Obtiene el perfil de paciente del usuario autenticado en la aplicación.
     *
     * Aunque las rutas están protegidas por Sanctum, el token podría pertenecer
     * a un usuario del panel. La comprobación evita errores de tipo y restringe
     * estos endpoints a pacientes con un perfil asociado.
     *
     * @param  Request  $request  Solicitud de la que se obtiene el usuario autenticado.
     * @return Paciente Perfil asociado al usuario autenticado.
     */
    private function pacienteAutenticado(Request $request): Paciente
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->isA(User::ROL_PACIENTE), 403);

        $paciente = $user->paciente;

        abort_unless($paciente instanceof Paciente, 403);

        return $paciente;
    }
}
