<?php

namespace App\Services;

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
            case 'superusuario':
                $viewName = 'Index/Supervisor';
                $viewData = $this->getDataForUsuariosPage($request->only(['rol', 'pais', 'nombre', 'orderBy', 'orderDir']));
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

    public function getDataForUsuariosPage(array $filters = [])
    {
        $usuarioService = new UsuarioService();
        $rolService = new RolService();
        $paisService = new PaisService();
        $centroSaludService = new CentroSaludService();

        if (empty($filters['orderBy'])) {
            $filters['orderBy'] = 'id';
        }

        if (empty($filters['orderDir'])) {
            $filters['orderDir'] = 'desc';
        }

        $usuarios = $usuarioService->queryGetUsuarios($filters)
            ->paginate(10)
            ->withQueryString();

        $roles = $rolService->queryGetRoles([
            'excepto' => 'paciente',
            'orderBy' => 'name',
            'orderDir' => 'asc',
        ])->get();

        $paises = $paisService->queryGetPaises([
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
}
