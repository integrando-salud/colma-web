<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Paciente\SyncDataRequest;
use App\Models\Paciente;
use App\Models\User;
use App\Services\AlertaService;
use App\Services\DatosClinicosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Expone las operaciones de la aplicación móvil que solo puede realizar el
 * usuario que posee el perfil de paciente.
 */
class PacienteController extends Controller
{
    /**
     * Sincroniza la información clínica enviada por el paciente autenticado.
     *
     * El identificador de usuario se toma de la sesión autenticada, aun cuando
     * el cliente lo incluya en la carga, para impedir sincronizar datos de otro
     * paciente.
     *
     * @param  SyncDataRequest  $request  Solicitud con la información clínica validada.
     * @param  DatosClinicosService  $datosClinicosService  Servicio que persiste la sincronización de forma atómica.
     * @return JsonResponse Respuesta que informa el resultado de la sincronización.
     */
    public function syncData(SyncDataRequest $request, DatosClinicosService $datosClinicosService): JsonResponse
    {
        $paciente = $this->pacienteAutenticado($request);

        try {
            $data = $request->validated();
            $data['userId'] = $paciente->user_id;

            $datosClinicosService->syncDatosApp($data);

            return response()->json([
                'message' => 'Datos sincronizados con éxito.',
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Error al sincronizar los datos.',
            ], 500);
        }
    }

    /**
     * Registra una alerta operativa para el paciente autenticado que solicita ayuda.
     *
     * @param  Request  $request  Solicitud autenticada de la aplicación móvil.
     * @param  AlertaService  $alertaService  Servicio responsable de crear la alerta operativa.
     * @return JsonResponse Respuesta que informa el resultado del registro.
     */
    public function pedidoAyuda(Request $request, AlertaService $alertaService): JsonResponse
    {
        $paciente = $this->pacienteAutenticado($request);

        try {
            $alertaService->savePedidoAyuda($paciente->id);

            return response()->json([
                'message' => 'Pedido de ayuda registrado con éxito.',
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Error al registrar el pedido de ayuda.',
            ], 500);
        }
    }

    /**
     * Obtiene el perfil de paciente del usuario autenticado en la aplicación móvil.
     *
     * @param  Request  $request  Solicitud cuya sesión se debe validar.
     * @return Paciente Perfil de paciente asociado al usuario autenticado.
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
