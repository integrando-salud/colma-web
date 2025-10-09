<?php

namespace App\Services;

use App\Http\Requests\Dashboard\User\FilterUserRequest;
use App\Http\Resources\Dashboard\CentroSaludResource;
use App\Http\Resources\Dashboard\PaisResource;
use App\Http\Resources\Dashboard\RolResource;
use App\Http\Resources\Dashboard\UserResource;
use Illuminate\Http\Request;

class DashboardService
{
    public function handleIndexView(Request $request)
    {
        switch (auth()->user()->roles->first()->name) {
            case 'superadmin':
                $viewName = 'Index/Supervisor';
                $viewData = $this->getDataForIndexSupervisor($request->only(['rol', 'pais', 'nombre']));
                break;
            case 'supervisor':
                $viewName = 'Index/Supervisor';
                $viewData = [];
                break;
            case 'supervisor_pais':
                $viewName = 'Index/Supervisor';
                $viewData = [];
                break;
            case 'promotor':
                $viewName = 'Index/Promotor';
                $viewData = [];
                break;
        }

        return [
            'name' => $viewName,
            'data' => $viewData,
        ];
    }

    public function getDataForIndexSupervisor(array $filters = [])
    {
        $usuarioService = new UsuarioService();
        $rolService = new RolService();
        $paisService = new PaisService();
        $centroSaludService = new CentroSaludService();

        $usuarios = $usuarioService->queryGetUsuarios(['orderDir' => 'desc'] + $filters)
            ->paginate(10)
            ->withQueryString();

        $roles = $rolService->queryGetRoles([
            'exclude' => 'paciente',
            'orderBy' => 'name',
            'orderDir' => 'asc',
        ])->get();

        $paises = $paisService->queryGetPaises([
            'active' => true,
            'orderBy' => 'name',
            'orderDir' => 'asc',
        ])->get();

        $centrosSalud = $centroSaludService->queryGetCentrosSalud([
            'active' => true,
            'orderBy' => 'name',
            'orderDir' => 'asc',
        ])->get();

        $supervisores = $usuarioService->queryGetUsuarios([
            'rol' => 'supervisor',
            'orderBy' => 'name',
        ])->get();

        return [
            'usuarios' => UserResource::collection($usuarios),
            'roles' => RolResource::collection($roles),
            'paises' => PaisResource::collection($paises),
            'centros_salud' => CentroSaludResource::collection($centrosSalud),
            'supervisores' => UserResource::collection($supervisores),
            'filtros' => $filters,
        ];
    }
}
