<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\User\GetUserRequest;
use App\Http\Requests\Dashboard\CentroSalud\GetCentroSaludRequest;
use App\Http\Requests\Dashboard\Diagnostico\GetDiagnosticoRequest;
use App\Http\Resources\Dashboard\CentroSaludResource;
use App\Http\Resources\Dashboard\DiagnosticoResource;
use App\Http\Resources\Dashboard\EntrenamientoResource;
use App\Http\Resources\Dashboard\PacienteResource;
use App\Http\Resources\Dashboard\UserResource;
use App\Models\Paciente;
use App\Models\User;
use App\Services\CentroSaludService;
use App\Services\DiagnosticoService;
use App\Services\PacienteService;
use App\Services\UsuarioService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AjaxController extends Controller
{
    public function getCentrosSalud(GetCentroSaludRequest $request, CentroSaludService $centroSaludService)
    {
        try {
            $centrosSalud = $centroSaludService->queryGetCentrosSalud($request->validated())->get();

            return CentroSaludResource::collection($centrosSalud);
        } catch (Throwable $th) {
            Log::error('Error al obtener los centros de salud: ' . $th->getMessage(), [
                'stack' => $th->getTraceAsString(),
            ]);

            return back()->with('error', 'Error al obtener los centros de salud.');
        }
    }

    public function getUsuarios(GetUserRequest $request, UsuarioService $usuarioService)
    {
        try {
            $usuarios = $usuarioService->queryGetUsuarios($request->validated())->get();

            return UserResource::collection($usuarios);
        } catch (Throwable $th) {
            Log::error('Error al obtener los usuarios: ' . $th->getMessage(), [
                'stack' => $th->getTraceAsString(),
            ]);

            return back()->with('error', 'Error al obtener los usuarios.');
        }
    }

    public function getDiagnosticos(GetDiagnosticoRequest $request, DiagnosticoService $diagnosticoService)
    {
        try {
            $diagnosticos = $diagnosticoService->queryGetDiagnosticos($request->validated())->get();

            return DiagnosticoResource::collection($diagnosticos);
        } catch (Throwable $th) {
            Log::error('Error al obtener los diagnósticos: ' . $th->getMessage(), [
                'stack' => $th->getTraceAsString(),
            ]);

            return back()->with('error', 'Error al obtener los diagnósticos.');
        }
    }

    public function contarPacientesActivos(User $usuario, UsuarioService $usuarioService)
    {
        try {
            $pacientesActivos = $usuarioService->countPacientesActivos($usuario->id);

            return response()->json(['pacientes_activos' => $pacientesActivos]);
        } catch (Throwable $th) {
            Log::error('Error al contar los pacientes activos: ' . $th->getMessage(), [
                'stack' => $th->getTraceAsString(),
            ]);

            session()->flash('error', 'Se produjo un error al contar los pacientes activos.');

            return response()->json([], 500);
        }
    }

    public function cambiarEstadoPaciente(Paciente $paciente, PacienteService $pacienteService, UsuarioService $usuarioService)
    {
        try {
            DB::beginTransaction();

            $pacienteService->cambiarEstado($paciente);
            $paciente->refresh();

            if ($paciente->activo) {
                $usuarioService->reactivateUsuario($paciente->usuario);
            } else {
                $usuarioService->deleteUsuario($paciente->usuario);
            }

            DB::commit();

            $accion = match ($paciente->activo) {
                true => 'activado',
                false => 'desactivado',
            };

            return response()->json([
                'message' => "Paciente $accion correctamente.",
                'activo' => $paciente->activo,
            ]);
        } catch (Throwable $th) {
            DB::rollBack();
            $accion = match ($paciente->activo) {
                true => 'desactivar',
                false => 'activar',
            };

            Log::error("Error $accion el paciente: " . $th->getMessage(), [
                'stack' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'message' => "Se produjo un error al $accion el paciente.",
            ], 500);
        }
    }

    public function contarContactosPendientes(User $usuario, UsuarioService $usuarioService)
    {
        try {
            $contactosPendientes = $usuarioService->countPacientesConContactosPendientes($usuario->id);

            return response()->json(['contactos_pendientes' => $contactosPendientes]);
        } catch (Throwable $th) {
            Log::error('Error al contar los contactos pendientes: ' . $th->getMessage(), [
                'stack' => $th->getTraceAsString(),
            ]);

            session()->flash('error', 'Se produjo un error al contar los contactos pendientes.');

            return response()->json([], 500);
        }
    }

    public function contarPacientesConAlertasPendientes(User $usuario, UsuarioService $usuarioService)
    {
        try {
            $pacientesConAlertasPendientes = $usuarioService->countPacientesConAlertas($usuario->id);

            return response()->json(['pacientes_con_alertas_pendientes' => $pacientesConAlertasPendientes]);
        } catch (Throwable $th) {
            Log::error('Error al contar los pacientes con alertas pendientes: ' . $th->getMessage(), [
                'stack' => $th->getTraceAsString(),
            ]);

            session()->flash('error', 'Se produjo un error al contar los pacientes con alertas pendientes.');

            return response()->json([], 500);
        }
    }

    public function getEntrenamientoActual(Paciente $paciente, PacienteService $pacienteService)
    {
        try {
            $entrenamientoActual = $pacienteService->getEntrenamientoActual($paciente);

            if (!$entrenamientoActual) {
                return null;
            };

            return response()->json(EntrenamientoResource::make($entrenamientoActual));
        } catch (Throwable $th) {
            Log::error('Error al obtener el entrenamiento actual: ' . $th->getMessage(), [
                'stack' => $th->getTraceAsString(),
            ]);

            session()->flash('error', 'Se produjo un error al obtener el entrenamiento actual.');

            return response()->json([], 500);
        }
    }

    public function resetearDatosPaciente(Paciente $paciente, PacienteService $pacienteService)
    {
        try {
            $pacienteService->resetearDatosPacienteTest($paciente);

            $paciente->refresh();

            return response()->json(PacienteResource::make($paciente));
        } catch (Throwable $th) {
            Log::error('Error al resetear los datos del paciente: ' . $th->getMessage(), [
                'stack' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Se produjo un error al resetear los datos del paciente.',
            ], 500);
        }
    }
}
