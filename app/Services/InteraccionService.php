<?php

namespace App\Services;

use App\Enums\MotivoInteraccion;
use App\Models\Alerta;
use App\Models\Entrenamiento;
use App\Models\Interaccion;
use App\Models\Paciente;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class InteraccionService
{
    /**
     * Crea el servicio con la dependencia que determina las sesiones del tratamiento.
     */
    public function __construct(private readonly TratamientoService $tratamientoService)
    {
    }

    /**
     * Crea o actualiza una interacción y sincroniza sus efectos en alertas y entrenamientos.
     *
     * Los datos deben haber sido validados por StoreInteraccionRequest. Todas las escrituras se
     * realizan en una misma transacción para no dejar una interacción guardada parcialmente.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable Cuando falla la transacción, la persistencia o una operación dependiente.
     */
    public function storeInteraccion(array $data): bool
    {
        return DB::transaction(function () use ($data): bool {
            $alertaId = data_get($data, 'alerta_id');
            $userId = auth()->id();
            $esNueva = empty($data['id']);
            $interaccion = $esNueva
                ? new Interaccion()
                : Interaccion::query()->findOrFail($data['id']);

            $interaccion->fill([
                'paciente_id' => $data['paciente_id'],
                // user_id identifica siempre a quien creó la interacción.
                // El último usuario que la modificó se guarda por separado.
                ...($esNueva
                    ? ['user_id' => $userId]
                    : ['updated_by_user_id' => $userId]),
                'alerta_id' => $alertaId,
                'contactado' => $data['contactado'],
                'fecha' => $this->fechaContacto($data),
                'hora' => $this->horaContacto($data),
                'modalidad' => $data['modalidad'],
                'motivo' => $data['motivo'],
                'observaciones' => data_get($data, 'observaciones'),
                'motivo_reprogramacion' => data_get($data, 'motivo_reprogramacion'),
                'fecha_cita' => $this->fechaDatabase(data_get($data, 'fecha_cita')),
                'hora_cita' => data_get($data, 'hora_cita'),
                'lugar_cita' => data_get($data, 'lugar_cita'),
                'lugar_cita_otro' => data_get($data, 'lugar_cita_otro'),
                'fecha_cita_final' => $this->fechaDatabase(data_get($data, 'fecha_cita_final')),
                'hora_cita_final' => data_get($data, 'hora_cita_final'),
                'seguimiento_pregunta_a' => data_get($data, 'seguimiento_pregunta_a'),
                'seguimiento_pregunta_b' => data_get($data, 'seguimiento_pregunta_b'),
                'seguimiento_pregunta_c' => data_get($data, 'seguimiento_pregunta_c'),
                'seguimiento_pregunta_d' => data_get($data, 'seguimiento_pregunta_d'),
                'seguimiento_pregunta_e' => data_get($data, 'seguimiento_pregunta_e'),
                'fecha_reentrenamiento' => $this->fechaDatabase(data_get($data, 'fecha_reentrenamiento')),
                'hora_reentrenamiento' => data_get($data, 'hora_reentrenamiento'),
                'lugar_reentrenamiento' => data_get($data, 'lugar_reentrenamiento'),
                'lugar_reentrenamiento_otro' => data_get($data, 'lugar_reentrenamiento_otro'),
                'razon_contacto_participante' => data_get($data, 'razon_contacto_participante'),
                'razon_contacto_participante_otro' => data_get($data, 'razon_contacto_participante_otro'),
                'contacto_participante_acciones' => data_get($data, 'contacto_participante_acciones'),
                'nueva_fecha_contacto' => $this->fechaDatabase(data_get($data, 'nueva_fecha_contacto')),
                'no_adherencia_pregunta_a' => data_get($data, 'no_adherencia_pregunta_a'),
                'no_adherencia_otro' => data_get($data, 'no_adherencia_otro'),
                'no_adherencia_pregunta_b' => data_get($data, 'no_adherencia_pregunta_b'),
                'no_adherencia_pregunta_c' => $this->fechaDatabase(data_get($data, 'no_adherencia_pregunta_c')),
                'problemas_conexion_pregunta_a' => data_get($data, 'problemas_conexion_pregunta_a'),
                'problemas_conexion_otro' => data_get($data, 'problemas_conexion_otro'),
                'problemas_conexion_pregunta_b' => data_get($data, 'problemas_conexion_pregunta_b'),
                'problemas_conexion_pregunta_c' => $this->fechaDatabase(data_get($data, 'problemas_conexion_pregunta_c')),
                'alerta_clinica_pregunta_a' => data_get($data, 'alerta_clinica_pregunta_a'),
                'alerta_clinica_pregunta_b' => data_get($data, 'alerta_clinica_pregunta_b'),
                'alerta_clinica_pregunta_c' => data_get($data, 'alerta_clinica_pregunta_c'),
                'alerta_clinica_pregunta_d' => data_get($data, 'alerta_clinica_pregunta_d'),
                'alerta_clinica_pregunta_e' => data_get($data, 'alerta_clinica_pregunta_e'),
                'alerta_clinica_pregunta_f' => data_get($data, 'alerta_clinica_pregunta_f'),
                'pedido_ayuda_pregunta_a' => data_get($data, 'pedido_ayuda_pregunta_a'),
                'pedido_ayuda_pregunta_b' => data_get($data, 'pedido_ayuda_pregunta_b'),
                'pedido_ayuda_pregunta_c' => data_get($data, 'pedido_ayuda_pregunta_c'),
                'pedido_ayuda_pregunta_d' => $this->fechaDatabase(data_get($data, 'pedido_ayuda_pregunta_d')),
            ]);
            $interaccion->save();

            if ($interaccion->motivo === MotivoInteraccion::CITA_ENTRENAMIENTO && $interaccion->contactado) {
                $entrenamiento = $this->guardarEntrenamientoModelo(
                    $interaccion->paciente_id,
                    $interaccion->fecha->toDateString(),
                    data_get($data, 'id_entrenamiento')
                );

                // Al reagendar se actualiza un entrenamiento existente; no es propiedad de
                // esta interacción y, por lo tanto, no debe borrarse junto con ella.
                if ($interaccion->entrenamiento_id || ! data_get($data, 'id_entrenamiento')) {
                    $interaccion->entrenamiento()->associate($entrenamiento);
                    $interaccion->save();
                }
            }

            if ($alertaId) {
                $dataAlerta = [
                    'revisado' => $interaccion->contactado ? now() : null,
                    'user_id' => $userId,
                ];

                if (! $interaccion->contactado) {
                    $dataAlerta['fecha_contacto'] = $interaccion->nueva_fecha_contacto;
                }

                Alerta::query()->whereKey($alertaId)->update($dataAlerta);
            }

            return true;
        });
    }

    /**
     * Elimina lógicamente una interacción.
     */
    public function deleteInteraccion(Interaccion $interaccion): bool
    {
        return DB::transaction(function () use ($interaccion): bool {
            // La relación se asigna únicamente al guardar una cita de entrenamiento contactada.
            // Por ello, el entrenamiento asociado es propiedad de esta interacción.
            $interaccion->entrenamiento()->delete();

            return (bool) $interaccion->delete();
        });
    }

    /**
     * Crea o actualiza el cronograma de entrenamiento de un paciente.
     *
     * Un entrenamiento existente no puede reasignarse a otro paciente.
     *
     * @throws Exception Cuando no se puede determinar el país del paciente.
     * @throws InvalidArgumentException Cuando el entrenamiento pertenece a otro paciente.
     * @throws ModelNotFoundException Cuando no existe el paciente o entrenamiento.
     */
    public function guardarEntrenamiento(int $idPaciente, string $fechaEntrenamiento, ?int $idEntrenamiento = null): bool
    {
        return $this->guardarEntrenamientoModelo($idPaciente, $fechaEntrenamiento, $idEntrenamiento)->exists;
    }

    /**
     * Crea o actualiza un entrenamiento y devuelve la instancia persistida.
     *
     * @throws Exception Cuando no se puede determinar el país del paciente.
     * @throws InvalidArgumentException Cuando el entrenamiento pertenece a otro paciente.
     * @throws ModelNotFoundException Cuando no existe el paciente o entrenamiento.
     */
    private function guardarEntrenamientoModelo(int $idPaciente, string $fechaEntrenamiento, ?int $idEntrenamiento = null): Entrenamiento
    {
        $entrenamiento = $idEntrenamiento
            ? Entrenamiento::query()->findOrFail($idEntrenamiento)
            : new Entrenamiento();
        $esNuevo = ! $entrenamiento->exists;
        $paciente = Paciente::query()->findOrFail($idPaciente);

        if (! $esNuevo && $entrenamiento->paciente_id !== $paciente->id) {
            throw new InvalidArgumentException('El entrenamiento no pertenece al paciente indicado.');
        }

        $entrenamiento->paciente_id = $paciente->id;
        $entrenamiento->user_id = auth()->id();
        $entrenamiento->fecha = $fechaEntrenamiento;
        $entrenamiento->sesiones = $this->tratamientoService->getCantidadSesionesPaciente($paciente);

        foreach (Entrenamiento::DIAS_SUMA as $tipo => $diasSuma) {
            $entrenamiento->{$tipo} = $this->sumarDias($fechaEntrenamiento, $diasSuma);
        }

        $entrenamiento->save();

        return $entrenamiento;
    }

    /**
     * Calcula una fecha del cronograma a partir de la fecha de entrenamiento.
     */
    private function sumarDias(string $fechaEntrenamiento, int $diasSuma): string
    {
        return CarbonImmutable::createFromFormat('!Y-m-d', $fechaEntrenamiento)->addDays($diasSuma)->toDateString();
    }

    /**
     * Obtiene la fecha efectiva de contacto; para una cita realizada usa la fecha final si falta.
     *
     * @param  array<string, mixed>  $data
     */
    private function fechaContacto(array $data): ?string
    {
        $fecha = data_get($data, 'fecha');

        if (! $fecha && $this->esCitaRealizada($data)) {
            $fecha = data_get($data, 'fecha_cita_final');
        }

        return $this->fechaDatabase($fecha);
    }

    /**
     * Obtiene la hora efectiva de contacto; para una cita realizada usa la hora final si falta.
     *
     * @param  array<string, mixed>  $data
     */
    private function horaContacto(array $data): ?string
    {
        $hora = data_get($data, 'hora');

        if (! $hora && $this->esCitaRealizada($data)) {
            $hora = data_get($data, 'hora_cita_final');
        }

        return $hora ?: null;
    }

    /**
     * Indica si los datos corresponden a una cita realizada con el participante contactado.
     *
     * @param  array<string, mixed>  $data
     */
    private function esCitaRealizada(array $data): bool
    {
        return data_get($data, 'contactado')
            && MotivoInteraccion::tryFrom(data_get($data, 'motivo'))?->esCitaRealizada() === true;
    }

    /**
     * Convierte una fecha DD/MM/AAAA validada por el formulario a formato de base de datos.
     */
    private function fechaDatabase(?string $fecha): ?string
    {
        if (! $fecha) {
            return null;
        }

        $fechaNormalizada = CarbonImmutable::createFromFormat('!d/m/Y', $fecha);

        if ($fechaNormalizada->format('d/m/Y') !== $fecha) {
            throw new InvalidArgumentException('La fecha debe tener el formato DD/MM/AAAA.');
        }

        return $fechaNormalizada->toDateString();
    }
}
