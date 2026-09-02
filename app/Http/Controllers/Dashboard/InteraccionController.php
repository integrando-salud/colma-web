<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Interacciones\DeleteInteraccionRequest;
use App\Http\Requests\Dashboard\Interacciones\StoreInteraccionRequest;
use App\Models\Alerta;
use App\Models\Interaccion;
use App\Models\Paciente;
use App\Models\User;
use App\Services\InteraccionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class InteraccionController extends Controller
{
    /**
     * Crea una interacción o actualiza una existente para un paciente autorizado.
     *
     * Las actualizaciones quedan limitadas a superusuarios y supervisores de país, igual que
     * la pantalla de edición. También se comprueba que una interacción existente pertenezca al
     * paciente recibido, para evitar que se la reasigne mediante una petición manipulada.
     */
    public function store(StoreInteraccionRequest $request, InteraccionService $interaccionService): RedirectResponse
    {
        $data = $request->validated();
        $paciente = $this->obtenerPacienteAutorizado($data['paciente_id']);
        $this->validarAlertaDelPaciente($data['alerta_id'] ?? null, $paciente);

        if (isset($data['id'])) {
            $this->autorizarEdicion();

            $interaccion = Interaccion::query()->findOrFail($data['id']);
            abort_unless(
                $interaccion->paciente_id === $paciente->id,
                422,
                'La interacción no pertenece al paciente indicado.'
            );
        }

        try {
            $interaccionService->storeInteraccion($data);

            $rutaDestino = isset($data['alerta_id'])
                ? 'dashboard.pacientes.alertas'
                : 'dashboard.pacientes.interacciones';

            return to_route($rutaDestino, [
                'paciente' => $paciente->id,
            ])->with('success', 'Se guardó con éxito.');
        } catch (Throwable $exception) {
            Log::error('Error al guardar interacción.', [
                'exception' => $exception,
            ]);

            return back()->with('error', 'Error al guardar la interacción.');
        }
    }

    /**
     * Elimina lógicamente una interacción y redirige al historial de su paciente.
     *
     * DeleteInteraccionRequest restringe esta operación a superusuarios y supervisores de país;
     * aquí se verifica además que el paciente esté dentro del alcance del usuario.
     */
    public function delete(DeleteInteraccionRequest $request, InteraccionService $interaccionService): RedirectResponse
    {
        $interaccion = Interaccion::query()->findOrFail($request->integer('interaccion'));
        $paciente = $this->obtenerPacienteAutorizado($interaccion->paciente_id);

        try {
            $interaccionService->deleteInteraccion($interaccion);

            return to_route($interaccion->alerta_id
                ? 'dashboard.pacientes.alertas.historial'
                : 'dashboard.pacientes.interacciones', [
                'paciente' => $paciente->id,
            ])->with('success', 'Se eliminó con éxito.');
        } catch (Throwable $exception) {
            Log::error('Error al eliminar interacción.', [
                'exception' => $exception,
            ]);

            return back()->with('error', 'Error al eliminar la interacción.');
        }
    }

    /**
     * Obtiene un paciente y confirma que el usuario autenticado puede consultarlo.
     */
    private function obtenerPacienteAutorizado(int $pacienteId): Paciente
    {
        $paciente = Paciente::query()->findOrFail($pacienteId);

        $this->authorize('view', $paciente);

        return $paciente;
    }

    /**
     * Confirma que el usuario puede editar interacciones existentes.
     */
    private function autorizarEdicion(): void
    {
        abort_unless(
            auth()->user()->isA(User::ROL_SUPERUSUARIO, User::ROL_SUPERVISOR_PAIS),
            403,
            'No tienes permiso para realizar esta acción.'
        );
    }

    /**
     * Verifica que la alerta opcional corresponda al paciente de la interacción.
     *
     * Esto evita que una petición alterada actualice una alerta asociada a otro paciente.
     */
    private function validarAlertaDelPaciente(?int $alertaId, Paciente $paciente): void
    {
        if ($alertaId === null) {
            return;
        }

        abort_unless(
            Alerta::query()
                ->whereKey($alertaId)
                ->where('paciente_id', $paciente->id)
                ->exists(),
            422,
            'La alerta no pertenece al paciente indicado.'
        );
    }
}
