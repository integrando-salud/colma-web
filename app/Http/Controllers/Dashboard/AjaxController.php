<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\User\GetUserRequest;
use App\Http\Requests\Dashboard\CentroSalud\GetCentroSaludRequest;
use App\Http\Requests\Dashboard\Diagnostico\GetDiagnosticoRequest;
use App\Http\Requests\Dashboard\Paquete\GetPaqueteRequest;
use App\Http\Resources\Dashboard\CentroSaludResource;
use App\Http\Resources\Dashboard\DiagnosticoResource;
use App\Http\Resources\Dashboard\PaqueteResource;
use App\Http\Resources\Dashboard\UserResource;
use App\Models\Paciente;
use App\Models\User;
use App\Services\CentroSaludService;
use App\Services\DiagnosticoService;
use App\Services\PacienteService;
use App\Services\PaqueteService;
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

    public function getPaquetes(GetPaqueteRequest $request, PaqueteService $paqueteService)
    {
        try {
            $paquetes = $paqueteService->queryGetPaquetes($request->validated())->get();

            return PaqueteResource::collection($paquetes);
        } catch (Throwable $th) {
            Log::error('Error al obtener los paquetes: ' . $th->getMessage(), [
                'stack' => $th->getTraceAsString(),
            ]);

            return back()->with('error', 'Error al obtener los paquetes.');
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
}
