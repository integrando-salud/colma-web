<?php

namespace App\Services;

use App\Enums\MotivoInteraccion;
use App\Models\Alerta;
use App\Models\Entrenamiento;
use App\Models\Interaccion;
use App\Models\Paciente;
use Illuminate\Support\Facades\DB;
use Throwable;

class InteraccionService
{
    public function __construct(private TratamientoService $tratamientoService)
    {
    }

    public function storeInteraccion(array $data)
    {
        try {
            DB::beginTransaction();

            $alertaId = data_get($data, 'alerta_id');
            $fechaContacto = $this->fechaContacto($data);
            $horaContacto = $this->horaContacto($data);

            $interaccion = empty($data['id']) ? new Interaccion() : Interaccion::find($data['id']);
            $interaccion->paciente_id = $data['paciente_id'];
            $interaccion->user_id = auth()->user()->id;
            $interaccion->alerta_id = $alertaId;
            $interaccion->contactado = $data['contactado'];
            $interaccion->fecha = $fechaContacto;
            $interaccion->hora = $horaContacto;
            $interaccion->modalidad = $data['modalidad'];
            $interaccion->motivo = $data['motivo'];
            $interaccion->observaciones = data_get($data, 'observaciones');
            $interaccion->motivo_reprogramacion = data_get($data, 'motivo_reprogramacion');
            $interaccion->fecha_cita = $this->fechaDatabase(data_get($data, 'fecha_cita'));
            $interaccion->hora_cita = data_get($data, 'hora_cita');
            $interaccion->lugar_cita = data_get($data, 'lugar_cita');
            $interaccion->lugar_cita_otro = data_get($data, 'lugar_cita_otro');
            $interaccion->fecha_cita_final = $this->fechaDatabase(data_get($data, 'fecha_cita_final'));
            $interaccion->hora_cita_final = data_get($data, 'hora_cita_final');
            $interaccion->seguimiento_pregunta_a = data_get($data, 'seguimiento_pregunta_a');
            $interaccion->seguimiento_pregunta_b = data_get($data, 'seguimiento_pregunta_b');
            $interaccion->seguimiento_pregunta_c = data_get($data, 'seguimiento_pregunta_c');
            $interaccion->seguimiento_pregunta_d = data_get($data, 'seguimiento_pregunta_d');
            $interaccion->seguimiento_pregunta_e = data_get($data, 'seguimiento_pregunta_e');
            $interaccion->fecha_reentrenamiento = $this->fechaDatabase(data_get($data, 'fecha_reentrenamiento'));
            $interaccion->hora_reentrenamiento = data_get($data, 'hora_reentrenamiento');
            $interaccion->lugar_reentrenamiento = data_get($data, 'lugar_reentrenamiento');
            $interaccion->lugar_reentrenamiento_otro = data_get($data, 'lugar_reentrenamiento_otro');
            $interaccion->razon_contacto_participante = data_get($data, 'razon_contacto_participante');
            $interaccion->razon_contacto_participante_otro = data_get($data, 'razon_contacto_participante_otro');
            $interaccion->contacto_participante_acciones = data_get($data, 'contacto_participante_acciones');
            $interaccion->nueva_fecha_contacto = $this->fechaDatabase(data_get($data, 'nueva_fecha_contacto'));
            $interaccion->no_adherencia_pregunta_a = data_get($data, 'no_adherencia_pregunta_a');
            $interaccion->no_adherencia_otro = data_get($data, 'no_adherencia_otro');
            $interaccion->no_adherencia_pregunta_b = data_get($data, 'no_adherencia_pregunta_b');
            $interaccion->no_adherencia_pregunta_c = $this->fechaDatabase(data_get($data, 'no_adherencia_pregunta_c'));
            $interaccion->problemas_conexion_pregunta_a = data_get($data, 'problemas_conexion_pregunta_a');
            $interaccion->problemas_conexion_otro = data_get($data, 'problemas_conexion_otro');
            $interaccion->problemas_conexion_pregunta_b = data_get($data, 'problemas_conexion_pregunta_b');
            $interaccion->problemas_conexion_pregunta_c = $this->fechaDatabase(data_get($data, 'problemas_conexion_pregunta_c'));
            $interaccion->alerta_clinica_pregunta_a = data_get($data, 'alerta_clinica_pregunta_a');
            $interaccion->alerta_clinica_pregunta_b = data_get($data, 'alerta_clinica_pregunta_b');
            $interaccion->alerta_clinica_pregunta_c = data_get($data, 'alerta_clinica_pregunta_c');
            $interaccion->alerta_clinica_pregunta_d = data_get($data, 'alerta_clinica_pregunta_d');
            $interaccion->alerta_clinica_pregunta_e = data_get($data, 'alerta_clinica_pregunta_e');
            $interaccion->alerta_clinica_pregunta_f = data_get($data, 'alerta_clinica_pregunta_f');
            $interaccion->pedido_ayuda_pregunta_a = data_get($data, 'pedido_ayuda_pregunta_a');
            $interaccion->pedido_ayuda_pregunta_b = data_get($data, 'pedido_ayuda_pregunta_b');
            $interaccion->pedido_ayuda_pregunta_c = data_get($data, 'pedido_ayuda_pregunta_c');
            $interaccion->pedido_ayuda_pregunta_d = $this->fechaDatabase(data_get($data, 'pedido_ayuda_pregunta_d'));
            $interaccion->save();

            if ($interaccion->motivo === MotivoInteraccion::AGENDAR_CITA_ENTRENAMIENTO && $interaccion->contactado) {
                $this->guardarEntrenamiento($interaccion->paciente_id, $interaccion->fecha_cita->format('Y-m-d'), data_get($data, 'id_entrenamiento'));
            }

            if ($interaccion->motivo === MotivoInteraccion::CITA_ENTRENAMIENTO && !$interaccion->contactado) {
                $this->guardarEntrenamiento($interaccion->paciente_id, $interaccion->nueva_fecha_contacto->format('Y-m-d'), data_get($data, 'id_entrenamiento'));
            }

            if ($alertaId) {
                $dataAlerta = [
                    'revisado' => $interaccion->contactado ? now() : null,
                    'user_id' => auth()->user()->id,
                ];

                if (! $interaccion->contactado) {
                    $dataAlerta['fecha_contacto'] = $interaccion->nueva_fecha_contacto;
                }

                Alerta::where('id', $alertaId)->update($dataAlerta);
            }

            DB::commit();

            return true;
        } catch (Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function deleteInteraccion(Interaccion $interaccion)
    {
        return $interaccion->delete();
    }

    public function guardarEntrenamiento(int $idPaciente, string $fechaEntrenamiento, ?int $idEntrenamiento = null)
    {
        $entrenamiento = $idEntrenamiento ? Entrenamiento::find($idEntrenamiento) : new Entrenamiento();
        $esNuevo = ! $entrenamiento->exists;
        $entrenamiento->paciente_id = $idPaciente;
        $entrenamiento->user_id = auth()->user()->id;
        $entrenamiento->fecha = $fechaEntrenamiento;
        $entrenamiento->sesiones = $this->tratamientoService->getCantidadSesionesPaciente(Paciente::findOrFail($idPaciente));

        if ($esNuevo) {
            $entrenamiento->sesiones_completadas = 0;
        }

        foreach (Entrenamiento::DIAS_SUMA as $tipo => $diasSuma) {
            $entrenamiento->$tipo = $this->sumarDias($tipo, $fechaEntrenamiento, $diasSuma);
        }

        return $entrenamiento->save();
    }

    private function sumarDias(string $tipo, string $fechaEntrenamiento, int $diasSuma): string
    {
        return date('Y-m-d', strtotime($fechaEntrenamiento . ' +' . $diasSuma . ' days'));
    }

    private function fechaContacto(array $data): ?string
    {
        $fecha = data_get($data, 'fecha');

        if (! $fecha && $this->esCitaRealizada($data)) {
            $fecha = data_get($data, 'fecha_cita_final');
        }

        return $this->fechaDatabase($fecha);
    }

    private function horaContacto(array $data): ?string
    {
        $hora = data_get($data, 'hora');

        if (! $hora && $this->esCitaRealizada($data)) {
            $hora = data_get($data, 'hora_cita_final');
        }

        return $hora ?: null;
    }

    private function esCitaRealizada(array $data): bool
    {
        return data_get($data, 'contactado')
            && MotivoInteraccion::tryFrom(data_get($data, 'motivo'))?->esCitaRealizada() === true;
    }

    private function fechaDatabase(?string $fecha): ?string
    {
        if (! $fecha) {
            return null;
        }

        return implode('-', array_reverse(explode('/', $fecha)));
    }
}
