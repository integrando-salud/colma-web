<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\User\FilterUserRequest;
use App\Http\Requests\Dashboard\User\StoreUserRequest;
use App\Http\Requests\Dashboard\User\UpdatePasswordRequest;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\UsuarioService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Throwable;

class UsuarioController extends Controller
{
    public function index(FilterUserRequest $request, DashboardService $dashboardService)
    {
        $viewData = $dashboardService->getDataForUsuariosPage($request->validated());

        return Inertia::render('Usuarios', $viewData);
    }

    public function store(StoreUserRequest $request, UsuarioService $usuarioService)
    {
        try {
            $usuarioService->storeUsuario($request->validated());

            session()->flash('success', 'Usuario ' . ($request->filled('id') ? 'actualizado' : 'creado') . ' con éxito.');

            return response()->json();
        } catch (Throwable $th) {
            Log::error('Error al crear usuario: ' . $th->getMessage(), [
                'stack' => $th->getTraceAsString(),
            ]);

            return response()->json([], 500);
        }
    }

    public function delete(UsuarioService $usuarioService, User $user)
    {
        try {
            $usuarioService->deleteUsuario($user);

            session()->flash('success', 'Usuario eliminado con éxito.');

            return response()->json();
        } catch (Throwable $th) {
            Log::error('Error al eliminar usuario: ' . $th->getMessage(), [
                'stack' => $th->getTraceAsString(),
            ]);

            return response()->json([], 500);
        }
    }

    public function updatePassword(UpdatePasswordRequest $request, UsuarioService $usuarioService, User $user)
    {
        try {
            $usuarioService->updatePassword($user, $request->password);

            return response()->json();
        } catch (Throwable $th) {
            Log::error('Error al actualizar la contraseña: ' . $th->getMessage(), [
                'stack' => $th->getTraceAsString(),
            ]);

            return response()->json([], 500);
        }
    }
}
