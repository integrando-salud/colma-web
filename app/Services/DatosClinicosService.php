<?php

namespace App\Services;

use App\Models\Alerta;
use App\Models\Cuestionario;
use App\Models\EstadoAnimo;
use App\Models\Glucosa;
use App\Models\Meta;
use App\Models\MultimediaRead;
use App\Models\PresionArterial;
use Illuminate\Support\Facades\DB;
use Throwable;

class DatosClinicosService
{
    public function syncDatosApp(array $data)
    {
        try {
            $userId = $data['userId'];
            DB::beginTransaction();

            if (!empty($data['glucosa'])) {
                $this->saveDatosGlucosa($userId, $data['glucosa']);
            }

            if (!empty($data['presion'])) {
                $this->saveDatosPresionArterial($userId, $data['presion']);
            }

            if (!empty($data['estado_animo'])) {
                $this->saveDatosEstadoAnimo($userId, $data['estado_animo']);
            }

            if (!empty($data['quiz'])) {
                $this->saveDatosCuestionario($userId, $data['quiz']);
            }

            if (!empty($data['metas'])) {
                $this->saveDatosMetas($userId, $data['metas']);
            }

            if (!empty($data['multimedia_read'])) {
                $this->saveDatosMultimediaRead($userId, $data['multimedia_read']);
            }

            DB::commit();
            return true;
        } catch (Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    protected function saveDatosGlucosa(int $idPaciente, array $data)
    {
        foreach ($data as $d) {
            $registroGlucosa = Glucosa::create([
                'id_app' => $d['id_glucosa'],
                'paciente_id' => $idPaciente,
                'valor_mgdl' => $d['valor_mgdl'],
                'ayuno' => $d['ayuno'],
                'fecha_registro' => $d['fecha_registro'],
                'fecha_usuario' => $d['fecha_usuario'],
                'fecha_sincronizacion' => $d['fecha_sincronizacion'],
                'advertencia' => $d['advertencia'],
            ]);

            if ($registroGlucosa->advertencia !== 'Normal') {
                $descripcion = $registroGlucosa->advertencia === 'Baja'
                    ? 'Glucosa baja'
                    : 'Glucosa baja elevada / alta';

                $this->saveAlerta(
                    $idPaciente,
                    'clinica',
                    $registroGlucosa->id,
                    'glucosa',
                    $descripcion,
                    'Indicar que vaya a consulta'
                );
            }
        }
    }

    protected function saveDatosPresionArterial(int $idPaciente, array $data)
    {
        foreach ($data as $d) {
            $registroPresion = PresionArterial::create([
                'id_app' => $d['id_presion'],
                'paciente_id' => $idPaciente,
                'presion_sistolica' => $d['sistolica'],
                'presion_diastolica' => $d['diastolica'],
                'fecha_registro' => $d['fecha_registro'],
                'fecha_usuario' => $d['fecha_usuario'],
                'fecha_sincronizacion' => $d['fecha_sincronizacion'],
                'advertencia' => $d['advertencia'],
            ]);

            if ($registroPresion->advertencia !== 'Normal') {
                $descripcion = $registroPresion->advertencia === 'Baja'
                    ? 'Presión arterial baja'
                    : 'Presión arterial elevada / alta';

                $this->saveAlerta(
                    $idPaciente,
                    'clinica',
                    $registroPresion->id,
                    'presion_arterial',
                    $descripcion,
                    'Indicar que vaya a consulta'
                );
            }
        }
    }

    protected function saveDatosEstadoAnimo(int $idPaciente, array $data)
    {
        foreach ($data as $d) {
            $valor = 0;
            for ($i = 1; $i <= 9; $i++) {
                $valor += (int) $d['pregunta_' . $i];
            }

            $registroEstadoAnimo = EstadoAnimo::create([
                'id_app' => $d['id_estado_animo'],
                'paciente_id' => $idPaciente,
                'pregunta_1' => $d['pregunta_1'],
                'pregunta_2' => $d['pregunta_2'],
                'pregunta_3' => $d['pregunta_3'],
                'pregunta_4' => $d['pregunta_4'],
                'pregunta_5' => $d['pregunta_5'],
                'pregunta_6' => $d['pregunta_6'],
                'pregunta_7' => $d['pregunta_7'],
                'pregunta_8' => $d['pregunta_8'],
                'pregunta_9' => $d['pregunta_9'],
                'valor' => $valor,
                'fecha_registro' => $d['fecha_registro'],
                'fecha_usuario' => $d['fecha_usuario'],
                'fecha_sincronizacion' => $d['fecha_sincronizacion'],
            ]);

            if ($valor > config('app.valor_alerta_estado_animo')) {
                $this->saveAlerta(
                    $idPaciente,
                    'clinica',
                    $registroEstadoAnimo->id,
                    'estado_animo',
                    'Estado de ánimo alterado',
                    'Contactar al paciente para seguimiento'
                );
            }
        }
    }

    protected function saveAlerta(
        int $idPaciente,
        string $tipo,
        int $entidadId,
        string $entidad_tipo,
        string $descripcion,
        string $recomendacion
    )
    {
        return Alerta::create([
            'paciente_id' => $idPaciente,
            'tipo' => $tipo,
            'entidad_id' => $entidadId,
            'entidad_tipo' => $entidad_tipo,
            'descripcion' => $descripcion,
            'recomendacion' => $recomendacion,
            'fecha' => now(),
            'fecha_contacto' => now()->addDay(),
        ]);
    }

    protected function saveDatosCuestionario(int $idPaciente, array $data)
    {
        foreach ($data as $d) {
            $cuestionario = Cuestionario::create([
                'id_app' => $d['id_quiz'],
                'paciente_id' => $idPaciente,
                'quiz_type' => $d['quiz_type'],
                'fecha_registro' => $d['fecha_registro'],
                'fecha_usuario' => $d['fecha_usuario'],
                'fecha_sincronizacion' => $d['fecha_sincronizacion']
            ]);

            foreach ($d['preguntas'] as $p) {
                $cuestionario->preguntas()->create([
                    'pregunta_index' => $p['pregunta_index'],
                    'pregunta_texto' => $p['pregunta_texto'],
                    'respuesta_usuario' => $p['respuesta_usuario'],
                    'respuesta_usuario_texto' => $p['respuesta_usuario_texto'],
                    'respuesta_correcta' => $p['respuesta_correcta'],
                    'respuesta_correcta_texto' => $p['respuesta_correcta_texto'],
                    'es_correcta' => $p['es_correcta'],
                    'respuestas_posibles' => $p['posibles_respuestas'],
                ]);
            }
        }
    }

    protected function saveDatosMetas(int $idPaciente, array $data)
    {
        foreach ($data as $d) {
            Meta::create([
                'id_app' => $d['id_meta'],
                'paciente_id' => $idPaciente,
                'semana' => $d['semana'],
                'sesion' => $d['sesion'],
                'tipo_actividad' => $d['tipo_actividad'],
                'actividad' => $d['actividad'],
                'lugar' => $d['lugar'],
                'acompaniante' => data_get($d, 'acompaniante'),
                'meta_cumplida' => $d['meta_cumplida'],
                'motivo_incumplimiento' => $d['motivo_incumplimiento'],
                'fecha_registro' => $d['fecha_registro'],
                'fecha_usuario' => $d['fecha_usuario'],
                'fecha_sincronizacion' => $d['fecha_sincronizacion'],
                'fecha_edicion' => data_get($d, 'fecha_edicion'),
                'fecha_objetivo' => $d['fecha_objetivo'],
            ]);
        }
    }

    protected function saveDatosMultimediaRead(int $idPaciente, array $data)
    {
        foreach ($data as $d) {
            MultimediaRead::create([
                'id_app' => $d['id_multimedia_read'],
                'paciente_id' => $idPaciente,
                'tratamiento' => $d['treatment_id'],
                'semana' => $d['week_id'],
                'sesion' => $d['session_id'],
                'tipo' => $d['multimedia_type'],
                'key' => $d['multimedia_unique_key'],
                'fecha_registro' => $d['fecha_registro'],
                'fecha_sincronizacion' => $d['fecha_sincronizacion'],
            ]);
        }
    }
}
