<?php

namespace App\Http\Controllers\API;

use App\Services\MultimediaService;
use App\Services\TratamientoService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Expone el tratamiento y su material multimedia a usuarios de la aplicación
 * móvil que poseen un perfil de paciente.
 */
class TratamientoController extends PacienteAutenticadoController
{
    /**
     * Obtiene la estructura del tratamiento asignado al paciente autenticado.
     *
     * El paciente se determina exclusivamente a partir de la sesión para evitar
     * que un usuario autenticado consulte el tratamiento de otra persona. Un
     * paciente sin diagnóstico o sin versión de tratamiento compatible recibe
     * `null`.
     *
     * @param  Request  $request  Solicitud autenticada de la aplicación móvil.
     * @param  TratamientoService  $tratamientoService  Servicio que construye el tratamiento y sus recursos.
     * @return JsonResponse Respuesta JSON con el tratamiento o `null` si no corresponde uno.
     *
     * @throws Exception Cuando no se puede determinar el país del paciente.
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
     *
     * @throws Exception Cuando no se puede determinar el país del paciente.
     */
    public function getMultimedia(
        Request $request,
        string $path,
        TratamientoService $tratamientoService,
        MultimediaService $multimediaService,
    ): BinaryFileResponse {
        abort_unless(
            $tratamientoService->puedeAccederAMultimedia($this->pacienteAutenticado($request), $path),
            404,
        );

        return $multimediaService->descargarMultimedia($path);
    }
}
