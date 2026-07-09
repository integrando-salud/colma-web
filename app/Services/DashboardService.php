<?php

namespace App\Services;

use App\Http\Resources\Dashboard\CentroSaludResource;
use App\Http\Resources\Dashboard\PacienteResource;
use App\Http\Resources\Dashboard\PaisResource;
use App\Http\Resources\Dashboard\RolResource;
use App\Http\Resources\Dashboard\UserResource;
use Illuminate\Http\Request;

class DashboardService
{
    public function handleIndexView(Request $request)
    {
        $rolName = auth()->user()->roles->first()->name;

        switch ($rolName) {
            case 'superusuario':
            case 'supervisor':
            case 'supervisor_pais':
                $viewName = 'Index/UsuariosIndex';
                $viewData = $this->getDataForUsuariosPage($request->only(['rol', 'pais', 'nombre', 'orderBy', 'orderDir']));
                break;
            case 'promotor':
                $viewName = 'Index/PacientesIndex';
                $viewData = $this->getDataForPacientesPage($request->only('estado', 'alertas', 'nombre', 'orderBy', 'orderDir'));
                break;
        }

        return [
            'name' => $viewName,
            'data' => $viewData,
        ];
    }

    public function getDataForUsuariosPage(array $filters = [])
    {
        $usuarioService = new UsuarioService();
        $rolService = new RolService();
        $paisService = new PaisService();
        $centroSaludService = new CentroSaludService();
        $authUser = auth()->user();

        if (empty($filters['orderBy'])) {
            $filters['orderBy'] = 'id';
        }

        if (empty($filters['orderDir'])) {
            $filters['orderDir'] = 'desc';
        }

        if ($authUser->isA('supervisor') || $authUser->isA('supervisor_pais')) {
            $filters['pais'] = $authUser->pais_id;
        }

        $usuarios = $usuarioService->queryGetUsuarios($filters)
            ->paginate(config('app.rpp'))
            ->withQueryString();

        $rolesExcluidos = ['paciente'];
        if ($authUser->isA('supervisor')) {
            $rolesExcluidos = array_merge($rolesExcluidos, [
                'superusuario',
                'supervisor',
                'supervisor_pais',
            ]);
        } elseif ($authUser->isA('supervisor_pais')) {
            $rolesExcluidos = array_merge($rolesExcluidos, [
                'superusuario',
                'supervisor_pais',
            ]);
        }

        $roles = $rolService->queryGetRoles([
            'excepto' => $rolesExcluidos,
            'orderBy' => 'name',
            'orderDir' => 'asc',
        ])->get();

        $paises = $paisService->queryGetPaises([
            'id' => ($authUser->isA('supervisor') || $authUser->isA('supervisor_pais')) ? $authUser->pais_id : null,
            'activo' => true,
            'orderBy' => 'nombre',
            'orderDir' => 'asc',
        ])->get();

        $centrosSalud = $centroSaludService->queryGetCentrosSalud([
            'activo' => true,
            'orderBy' => 'nombre',
            'orderDir' => 'asc',
        ])->get();

        $supervisores = $usuarioService->queryGetUsuarios([
            'rol' => 'supervisor',
            'orderBy' => 'nombre',
            'orderDir' => 'asc',
        ])->get();

        return [
            'usuarios' => UserResource::collection($usuarios),
            'roles' => RolResource::collection($roles),
            'paises' => PaisResource::collection($paises),
            'centrosSalud' => CentroSaludResource::collection($centrosSalud),
            'supervisores' => UserResource::collection($supervisores),
            'filtros' => $filters,
        ];
    }

    public function getDataForPacientesPage(array $filters = [])
    {
        $pacienteService = new PacienteService();

        if (empty($filters['orderBy'])) {
            $filters['orderBy'] = 'id';
        }

        if (empty($filters['orderDir'])) {
            $filters['orderDir'] = 'desc';
        }

        $pacientes = $pacienteService->queryGetPacientes($filters)
            ->paginate(config('app.rpp'))
            ->withQueryString();

        return [
            'pacientes' => PacienteResource::collection($pacientes),
            'promotores' => auth()->user()->isA('supervisor')
                ? UserResource::collection(auth()->user()->promotores()->orderBy('nombre')->get())
                : null,
            'filtros' => $filters,
        ];
    }
}
