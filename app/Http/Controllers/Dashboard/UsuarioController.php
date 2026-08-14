<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\User\FilterUserRequest;
use App\Http\Requests\Dashboard\User\StoreUserRequest;
use App\Http\Requests\Dashboard\User\UpdatePasswordRequest;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\UsuarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class UsuarioController extends Controller
{
    /**
     * Muestra el listado de usuarios con los filtros validados y el alcance del actor.
     *
     * FilterUserRequest exige el permiso de consulta; DashboardService aplica además el
     * alcance por rol antes de preparar las propiedades de la página de Inertia.
     */
    public function index(FilterUserRequest $request, DashboardService $dashboardService): Response
    {
        $viewData = $dashboardService->getDataForUsuariosPage($request->validated());

        return Inertia::render('Usuarios', $viewData);
    }

    /**
     * Crea un usuario o actualiza el indicado por el campo opcional `id`.
     *
     * StoreUserRequest distingue los permisos de creación y edición. Los errores inesperados
     * se registran sin exponer detalles internos a la respuesta JSON.
     */
    public function store(StoreUserRequest $request, UsuarioService $usuarioService): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['id'])) {
            $this->authorize('update', User::query()->findOrFail($data['id']));
        }

        $this->autorizarDatosGestionados($data);

        try {
            $usuarioService->storeUsuario($data);

            session()->flash('success', 'Usuario ' . ($request->filled('id') ? 'actualizado' : 'creado') . ' con éxito.');

            return response()->json();
        } catch (Throwable $exception) {
            Log::error('Error al guardar usuario.', [
                'exception' => $exception,
            ]);

            return response()->json([], 500);
        }
    }

    /**
     * Elimina lógicamente el usuario resuelto por el enlace de ruta.
     *
     * La ruta y la autorización del usuario garantizan el permiso de eliminación; el servicio
     * conserva el registro para mantener la integridad de sus relaciones históricas.
     */
    public function delete(UsuarioService $usuarioService, User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        try {
            $usuarioService->deleteUsuario($user);

            session()->flash('success', 'Usuario eliminado con éxito.');

            return response()->json();
        } catch (Throwable $exception) {
            Log::error('Error al eliminar usuario.', [
                'exception' => $exception,
            ]);

            return response()->json([], 500);
        }
    }

    /**
     * Reemplaza la contraseña del usuario resuelto por la ruta.
     *
     * UpdatePasswordRequest valida el permiso y la nueva clave. El servicio delega el cifrado
     * al cast `hashed` del modelo User, por lo que nunca se persiste en texto plano.
     */
    public function updatePassword(UpdatePasswordRequest $request, UsuarioService $usuarioService, User $user): JsonResponse
    {
        $this->authorize('updatePassword', $user);
        $data = $request->validated();

        try {
            $usuarioService->updatePassword($user, $data['password']);

            return response()->json();
        } catch (Throwable $exception) {
            Log::error('Error al actualizar la contraseña.', [
                'exception' => $exception,
            ]);

            return response()->json([], 500);
        }
    }

    /**
     * Confirma que el rol, país y supervisor solicitados respeten el alcance del actor.
     *
     * Esta verificación complementa la policy del usuario existente y protege también la
     * creación y los cambios de rol ante peticiones que no pasan por la interfaz.
     *
     * @param array{rol: string, pais_id: int|string, supervisor_id?: int|string|null} $data
     */
    private function autorizarDatosGestionados(array $data): void
    {
        $actor = auth()->user();

        if ($actor->isA(User::ROL_SUPERUSUARIO)) {
            abort_unless($data['rol'] !== User::ROL_PACIENTE, 403);

            return;
        }

        if ($actor->isA(User::ROL_SUPERVISOR_PAIS)) {
            abort_unless(
                (int) $data['pais_id'] === $actor->pais_id
                    && in_array($data['rol'], [User::ROL_SUPERVISOR, User::ROL_PROMOTOR], true),
                403
            );

            return;
        }

        abort_unless(
            $actor->isA(User::ROL_SUPERVISOR)
                && (int) $data['pais_id'] === $actor->pais_id
                && $data['rol'] === User::ROL_PROMOTOR
                && (int) ($data['supervisor_id'] ?? 0) === $actor->id,
            403
        );
    }
}
