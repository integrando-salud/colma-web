<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\User\GetUserRequest;
use App\Http\Requests\Dashboard\CentroSalud\GetCentroSaludRequest;
use App\Http\Requests\Dashboard\Diagnostico\GetDiagnosticoRequest;
use App\Http\Resources\Dashboard\CentroSaludResource;
use App\Http\Resources\Dashboard\DiagnosticoResource;
use App\Http\Resources\Dashboard\EntrenamientoResource;
use App\Http\Resources\Dashboard\PacienteListResource;
use App\Http\Resources\Dashboard\UserResource;
use App\Models\Paciente;
use App\Models\User;
use App\Services\CentroSaludService;
use App\Services\DiagnosticoService;
use App\Services\PacienteService;
use App\Services\UsuarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AjaxController extends Controller
{
    /**
     * Obtiene los centros de salud que cumplen los filtros validados.
     *
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function getCentrosSalud(GetCentroSaludRequest $request, CentroSaludService $centroSaludService): AnonymousResourceCollection|JsonResponse
    {
        try {
            $centrosSalud = $centroSaludService->queryGetCentrosSalud($request->validated())->get();

            return CentroSaludResource::collection($centrosSalud);
        } catch (Throwable $th) {
            return $this->errorResponse('Error al obtener los centros de salud.', $th);
        }
    }

    /**
     * Obtiene los usuarios accesibles para el usuario autenticado según los filtros recibidos.
     *
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function getUsuarios(GetUserRequest $request, UsuarioService $usuarioService): AnonymousResourceCollection|JsonResponse
    {
        try {
            // Este endpoint alimenta exclusivamente el selector de promotores.
            // El alcance no depende de un parámetro manipulable por el cliente.
            $usuarios = $usuarioService->queryGetUsuarios([
                ...$request->validated(),
                'forSelector' => true,
                'onlyPromotores' => true,
            ])->get();

            return UserResource::collection($usuarios);
        } catch (Throwable $th) {
            return $this->errorResponse('Error al obtener los usuarios.', $th);
        }
    }

    /**
     * Obtiene los diagnósticos que cumplen los filtros validados.
     *
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function getDiagnosticos(GetDiagnosticoRequest $request, DiagnosticoService $diagnosticoService): AnonymousResourceCollection|JsonResponse
    {
        try {
            $diagnosticos = $diagnosticoService->queryGetDiagnosticos($request->validated())->get();

            return DiagnosticoResource::collection($diagnosticos);
        } catch (Throwable $th) {
            return $this->errorResponse('Error al obtener los diagnósticos.', $th);
        }
    }

    /**
     * Devuelve la cantidad de pacientes activos visibles para un usuario.
     */
    public function contarPacientesActivos(User $usuario, UsuarioService $usuarioService): JsonResponse
    {
        $this->ensureCurrentUser($usuario);

        try {
            $pacientesActivos = $usuarioService->countPacientesActivos($usuario->id);

            return response()->json(['pacientes_activos' => $pacientesActivos]);
        } catch (Throwable $th) {
            return $this->errorResponse('Se produjo un error al contar los pacientes activos.', $th);
        }
    }

    /**
     * Devuelve la cantidad total de pacientes visibles para un usuario.
     */
    public function contarPacientes(User $usuario, UsuarioService $usuarioService): JsonResponse
    {
        $this->ensureCurrentUser($usuario);

        try {
            $pacientes = $usuarioService->countPacientes($usuario->id);

            return response()->json(['pacientes' => $pacientes]);
        } catch (Throwable $th) {
            return $this->errorResponse('Se produjo un error al contar los pacientes.', $th);
        }
    }

    /**
     * Alterna el estado del paciente y sincroniza el estado de su usuario de acceso.
     *
     * Ambas modificaciones se confirman en una sola transacción para no dejar el
     * paciente y su usuario en estados incompatibles.
     */
    public function cambiarEstadoPaciente(Paciente $paciente, PacienteService $pacienteService, UsuarioService $usuarioService): JsonResponse
    {
        $this->authorize('changeStatus', $paciente);

        $accionPendiente = $paciente->activo ? 'desactivar' : 'activar';

        try {
            $activo = DB::transaction(function () use ($paciente, $pacienteService, $usuarioService): bool {
                $pacienteService->cambiarEstado($paciente);
                $paciente->refresh();

                if ($paciente->activo) {
                    $usuarioService->reactivateUsuario($paciente->usuario);
                } else {
                    $usuarioService->deleteUsuario($paciente->usuario);
                }

                return $paciente->activo;
            });

            $accion = match ($activo) {
                true => 'activado',
                false => 'desactivado',
            };

            return response()->json([
                'message' => "Paciente $accion correctamente.",
                'activo' => $activo,
            ]);
        } catch (Throwable $th) {
            return $this->errorResponse("Se produjo un error al $accionPendiente el paciente.", $th);
        }
    }

    /**
     * Devuelve la cantidad de pacientes con contactos pendientes visibles para un usuario.
     */
    public function contarContactosPendientes(User $usuario, UsuarioService $usuarioService): JsonResponse
    {
        $this->ensureCurrentUser($usuario);

        try {
            $contactosPendientes = $usuarioService->countPacientesConContactosPendientes($usuario->id);

            return response()->json(['contactos_pendientes' => $contactosPendientes]);
        } catch (Throwable $th) {
            return $this->errorResponse('Se produjo un error al contar los contactos pendientes.', $th);
        }
    }

    /**
     * Devuelve la cantidad de pacientes con alertas pendientes visibles para un usuario.
     */
    public function contarPacientesConAlertasPendientes(User $usuario, UsuarioService $usuarioService): JsonResponse
    {
        $this->ensureCurrentUser($usuario);

        try {
            $pacientesConAlertasPendientes = $usuarioService->countPacientesConAlertas($usuario->id);

            return response()->json(['pacientes_con_alertas_pendientes' => $pacientesConAlertasPendientes]);
        } catch (Throwable $th) {
            return $this->errorResponse('Se produjo un error al contar los pacientes con alertas pendientes.', $th);
        }
    }

    /**
     * Obtiene el entrenamiento activo más reciente del paciente.
     *
     * Devuelve 204 cuando el paciente todavía no tiene un entrenamiento activo.
     */
    public function getEntrenamientoActual(Paciente $paciente, PacienteService $pacienteService): JsonResponse|Response
    {
        $this->authorize('view', $paciente);

        try {
            $entrenamientoActual = $pacienteService->getEntrenamientoActual($paciente);

            if (!$entrenamientoActual) {
                return response()->noContent();
            }

            return response()->json(EntrenamientoResource::make($entrenamientoActual));
        } catch (Throwable $th) {
            return $this->errorResponse('Se produjo un error al obtener el entrenamiento actual.', $th);
        }
    }

    /**
     * Elimina los datos clínicos y de seguimiento de un paciente de prueba.
     *
     * Devuelve el paciente recargado con las relaciones necesarias para
     * reemplazar su fila en el listado del panel.
     */
    public function resetearDatosPaciente(Paciente $paciente, PacienteService $pacienteService): JsonResponse
    {
        $this->authorize('view', $paciente);

        try {
            DB::transaction(function () use ($paciente, $pacienteService): void {
                $pacienteService->resetearDatosPacienteTest($paciente);
            });

            $paciente->refresh();
            $pacienteService->cargarRelacionesParaResource($paciente);

            // La respuesta reemplaza una fila del listado y debe conservar los
            // datos que consume su modal "Ver más", incluida la clave permitida.
            return response()->json(PacienteListResource::make($paciente));
        } catch (Throwable $th) {
            return $this->errorResponse('Se produjo un error al resetear los datos del paciente.', $th);
        }
    }

    /**
     * Registra una excepción del endpoint y genera una respuesta JSON segura.
     */
    private function errorResponse(string $message, Throwable $exception): JsonResponse
    {
        Log::error($message, ['exception' => $exception]);

        return response()->json(['message' => $message], 500);
    }

    /**
     * Impide consultar los indicadores del panel de otro usuario.
     */
    private function ensureCurrentUser(User $usuario): void
    {
        abort_unless($usuario->is(auth()->user()), 403);
    }
}
