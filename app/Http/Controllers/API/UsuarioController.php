<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\User\GetUserRequest;
use App\Http\Resources\Dashboard\UserResource;
use App\Services\UsuarioService;
use Illuminate\Support\Facades\Log;
use Throwable;

class UsuarioController extends Controller
{
    public function get(GetUserRequest $request, UsuarioService $usuarioService)
    {
        try {
            $usuarios = $usuarioService->queryGetUsuarios($request->validated())->get();

            return UserResource::collection($usuarios);
        } catch (Throwable $th) {
            Log::error('Error al obtener los usuarios: ' . $th->getMessage(), [
                'stack' => $th->getTraceAsString(),
            ]);

            return back()->with('error', 'Se produjo un error al obtener los usuarios.');
        }
    }
}
