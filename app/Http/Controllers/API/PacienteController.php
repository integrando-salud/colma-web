<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\Paciente\SyncDataRequest;
use App\Services\AlertaService;
use App\Services\DatosClinicosService;
use App\Services\PacienteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Expone las operaciones de la aplicación móvil que solo puede realizar el
 * usuario que posee el perfil de paciente.
 */
class PacienteController extends PacienteAutenticadoController
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
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => 'Los datos de progreso no son válidos.',
                'errors' => $exception->errors(),
            ], 422);
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
     * Obtiene los elementos lógicos completados del entrenamiento vigente.
     *
     * La aplicación usa los identificadores retornados para restaurar el avance
     * luego de una reinstalación o un cambio de dispositivo. Solo se consideran
     * los estados actuales asociados al último entrenamiento activo del paciente.
     *
     * @param  Request  $request  Solicitud autenticada de la aplicación móvil.
     * @param  PacienteService  $pacienteService  Servicio que resuelve el entrenamiento actual.
     * @return JsonResponse Respuesta con los identificadores de elementos completados.
     */
    public function elementosCompletados(Request $request, PacienteService $pacienteService): JsonResponse
    {
        $entrenamiento = $pacienteService->getEntrenamientoActual($this->pacienteAutenticado($request));

        return response()->json([
            'elementos_completados' => $entrenamiento
                ? $entrenamiento->progresoElementos()
                    ->where('completado', true)
                    ->orderBy('tratamiento_elemento_id')
                    ->pluck('tratamiento_elemento_id')
                    ->values()
                    ->all()
                : [],
        ]);
    }

    /**
     * Restaura datos funcionales de QUIZ y METAS sin mezclar su contenido con
     * el estado pedagógico de progreso_elementos.
     */
    public function datosFuncionalesTratamiento(Request $request, PacienteService $pacienteService): JsonResponse
    {
        $entrenamiento = $pacienteService->getEntrenamientoActual($this->pacienteAutenticado($request));

        if (! $entrenamiento) {
            return response()->json(['quiz' => [], 'metas' => []]);
        }

        return response()->json([
            'quiz' => $entrenamiento->respuestasQuiz()
                ->orderBy('tratamiento_elemento_id')
                ->get()
                ->map(fn ($respuesta) => [
                    'tratamiento_elemento_id' => $respuesta->tratamiento_elemento_id,
                    'fecha_registro' => $respuesta->fecha_registro->format('Y-m-d H:i:s'),
                    'fecha_usuario' => $respuesta->fecha_usuario->format('Y-m-d H:i:s'),
                    'fecha_sincronizacion' => $respuesta->fecha_sincronizacion->format('Y-m-d H:i:s'),
                    'preguntas' => $respuesta->preguntas,
                ])->values(),
            'metas' => $entrenamiento->metas()
                ->orderBy('tratamiento_elemento_id')
                ->orderBy('id_meta')
                ->get()
                ->map(fn ($meta) => [
                    'id_meta' => $meta->id_meta,
                    'tratamiento_elemento_id' => $meta->tratamiento_elemento_id,
                    'tipo_actividad' => $meta->tipo_actividad,
                    'actividad' => $meta->actividad,
                    'lugar' => $meta->lugar,
                    'acompaniante' => $meta->acompaniante,
                    'meta_cumplida' => $meta->meta_cumplida,
                    'motivo_incumplimiento' => $meta->motivo_incumplimiento,
                    'fecha_registro' => $meta->fecha_registro->format('Y-m-d H:i:s'),
                    'fecha_usuario' => $meta->fecha_usuario->format('Y-m-d H:i:s'),
                    'fecha_sincronizacion' => $meta->fecha_sincronizacion->format('Y-m-d H:i:s'),
                    'fecha_edicion' => $meta->fecha_edicion?->format('Y-m-d H:i:s'),
                    'fecha_objetivo' => $meta->fecha_objetivo->format('Y-m-d H:i:s'),
                ])->values(),
        ]);
    }
}
