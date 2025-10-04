<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\User\StoreUserRequest;
use App\Services\CentroSaludService;
use App\Services\PaisService;
use App\Services\RolService;
use App\Services\UsuarioService;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    public function index(
        UsuarioService $usuarioService,
        RolService $rolService,
        PaisService $paisService,
        CentroSaludService $centroSaludService
    )
    {
        $usuarios = $usuarioService->queryGetUsuarios()
            ->orderBy('name')
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

        return view('dashboard.usuarios', compact('usuarios', 'roles', 'paises', 'centrosSalud'));
    }

    public function store(StoreUserRequest $request, UsuarioService $usuarioService)
    {

    }
}
