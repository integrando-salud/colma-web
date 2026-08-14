<?php

namespace App\Services;

use App\Enums\AdvertenciaClinica;
use App\Models\Alerta;
use App\Models\Cuestionario;
use App\Models\EstadoAnimo;
use App\Models\Glucosa;
use App\Models\Meta;
use App\Models\MultimediaRead;
use App\Models\PresionArterial;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class DatosClinicosService
{
    /**
     * Sincroniza los datos clínicos enviados por la aplicación móvil de un paciente.
     *
     * La operación es atómica: ante cualquier error no persiste datos parciales.
     *
     * @param array<string, mixed> $data Datos validados por SyncDataRequest.
     * @throws ModelNotFoundException Cuando no existe el usuario o el paciente.
     * @throws Throwable Cuando la transacción o la persistencia de los datos falla.
     */
    public function syncDatosApp(array $data): bool
    {
        $usuario = User::query()->findOrFail($data['userId']);
        $paciente = $usuario->paciente()->firstOrFail();
        $idPaciente = $paciente->id;

        return DB::transaction(function () use ($data, $paciente, $idPaciente): bool {
            if (!empty($data['glucosa'])) {
                $this->saveDatosGlucosa($idPaciente, $data['glucosa']);
            }

            if (!empty($data['presion'])) {
                $this->saveDatosPresionArterial($idPaciente, $data['presion']);
            }

            if (!empty($data['estado_animo'])) {
                $this->saveDatosEstadoAnimo($idPaciente, $data['estado_animo']);
            }

            if (!empty($data['quiz'])) {
                $this->saveDatosCuestionario($idPaciente, $data['quiz']);
            }

            if (!empty($data['metas'])) {
                $this->saveDatosMetas($idPaciente, $data['metas']);
            }

            if (!empty($data['multimedia_read'])) {
                $this->saveDatosMultimediaRead($idPaciente, $data['multimedia_read']);
            }

            $paciente->update([
                'fecha_ultima_sincronizacion' => now(),
            ]);

            return true;
        });
    }

    /**
     * Guarda o actualiza las mediciones de glucosa y crea sus alertas cuando corresponde.
     *
     * @param array<int, array<string, mixed>> $data
     */
    protected function saveDatosGlucosa(int $idPaciente, array $data): void
    {
        foreach ($data as $d) {
            $registroGlucosa = Glucosa::updateOrCreate([
                'paciente_id' => $idPaciente,
                'id_app' => $d['id_glucosa'],
            ], [
                'valor_mgdl' => $d['valor_mgdl'],
                'ayuno' => $d['ayuno'],
                'fecha_registro' => $d['fecha_registro'],
                'fecha_usuario' => $d['fecha_usuario'],
                'fecha_sincronizacion' => $d['fecha_sincronizacion'],
                'advertencia' => $d['advertencia'],
            ]);

            if ($registroGlucosa->wasRecentlyCreated && $registroGlucosa->advertencia !== AdvertenciaClinica::NORMAL) {
                $descripcion = $registroGlucosa->advertencia === AdvertenciaClinica::BAJA
                    ? 'Glucosa baja'
                    : 'Glucosa elevada / alta';

                $recomendacion = $this->getRecomendacionGlucosa($registroGlucosa->valor_mgdl);

                if ($recomendacion === null) {
                    continue;
                }

                $recomendacion .= PHP_EOL . 'Completar formulario de alerta clínica.';

                $this->saveAlerta(
                    $idPaciente,
                    'clinica',
                    $registroGlucosa->id,
                    'glucosa',
                    $descripcion,
                    $recomendacion,
                    now()->addDays(2)->format('Y-m-d')
                );
            }
        }
    }

    /** Obtiene la recomendación operativa para un valor de glucosa crítico. */
    public function getRecomendacionGlucosa(int $valor): ?string
    {
        if ($valor < 70) {
            return 'Contactar y preguntar si pudo agendar un control con su médico.';
        } elseif ($valor > 300) {
            return 'Contactar y preguntar si pudo acceder a atención en emergencias/urgencias.';
        }

        return null;
    }

    /**
     * Guarda o actualiza las mediciones de presión arterial y crea sus alertas cuando corresponde.
     *
     * @param array<int, array<string, mixed>> $data
     */
    protected function saveDatosPresionArterial(int $idPaciente, array $data): void
    {
        foreach ($data as $d) {
            $registroPresion = PresionArterial::updateOrCreate([
                'paciente_id' => $idPaciente,
                'id_app' => $d['id_presion'],
            ], [
                'presion_sistolica' => $d['sistolica'],
                'presion_diastolica' => $d['diastolica'],
                'fecha_registro' => $d['fecha_registro'],
                'fecha_usuario' => $d['fecha_usuario'],
                'fecha_sincronizacion' => $d['fecha_sincronizacion'],
                'advertencia' => $d['advertencia'],
            ]);

            if ($registroPresion->wasRecentlyCreated && $registroPresion->advertencia !== AdvertenciaClinica::NORMAL) {
                $descripcion = $registroPresion->advertencia === AdvertenciaClinica::BAJA
                    ? 'Presión arterial baja'
                    : 'Presión arterial elevada / alta';

                $recomendacion = $this->getRecomendacionPresionArterial(
                    $registroPresion->presion_sistolica,
                    $registroPresion->presion_diastolica,
                    $idPaciente
                );

                if ($recomendacion === null) {
                    continue;
                }

                $recomendacion .= PHP_EOL . 'Completar formulario de alerta clínica.';

                $this->saveAlerta(
                    $idPaciente,
                    'clinica',
                    $registroPresion->id,
                    'presion_arterial',
                    $descripcion,
                    $recomendacion,
                    now()->addDays(2)->format('Y-m-d')
                );
            }
        }
    }

    /**
     * Obtiene la recomendación para una medición de presión arterial.
     * La presión elevada requiere cuatro mediciones recientes elevadas en días distintos.
     */
    public function getRecomendacionPresionArterial(
        int $presionSistolica,
        int $presionDiastolica,
        int $idPaciente
    ): ?string
    {
        if ($presionSistolica >= 180 || $presionDiastolica >= 120) {
            return 'Contactar y preguntar si pudo acceder a atención en emergencias/urgencias.';
        } elseif ($this->isPresionElevada($presionSistolica, $presionDiastolica)) {
            // Esta alerta se dispara solo si la condición se cumple para los últimos 4 registros consecutivos y en días diferentes
            $cantidadRegistros = 4;
            $ultimosRegistros = $this->getUltimosRegistrosPresionArterial($idPaciente, $cantidadRegistros);

            if ($ultimosRegistros->count() < $cantidadRegistros) {
                return null;
            }

            $sonDiasDiferentes = $this->checkUltimosRegistrosFechasDiferentes($cantidadRegistros, $ultimosRegistros);

            if (!$sonDiasDiferentes) {
                return null;
            }

            $todosConPresionElevada = $ultimosRegistros->every(
                fn ($registro) => $this->isPresionElevada(
                    (int) $registro->presion_sistolica,
                    (int) $registro->presion_diastolica
                )
            );

            if (!$todosConPresionElevada) {
                return null;
            }

            return 'Contactar y preguntar si pudo agendar un control con su médico.';
        } elseif ($presionSistolica < 90) {
            return 'Contactar y preguntar si pudo acceder a atención médica.';
        }

        return null;
    }

    /** Determina si una medición está en el rango elevado no crítico. */
    private function isPresionElevada(int $presionSistolica, int $presionDiastolica): bool
    {
        return ($presionSistolica >= 140 && $presionSistolica <= 179) || ($presionDiastolica >= 90 && $presionDiastolica <= 119);
    }

    /** Recupera las últimas mediciones de presión arterial de un paciente. */
    public function getUltimosRegistrosPresionArterial(int $idPaciente, int $cantidadRegistros): Collection
    {
        return PresionArterial::query()
            ->where('paciente_id', $idPaciente)
            ->latest('fecha_registro')
            ->limit($cantidadRegistros)
            ->get();
    }

    /** Verifica que el conjunto tenga la cantidad esperada y una medición por día. */
    private function checkUltimosRegistrosFechasDiferentes(int $cantidadRegistros, Collection $ultimosRegistros): bool
    {
        $fechasUnicas = $ultimosRegistros
            ->pluck('fecha_registro')
            ->map(fn ($fechaRegistro) => Carbon::parse($fechaRegistro)->toDateString())
            ->unique();

        return $ultimosRegistros->count() === $cantidadRegistros
            && $fechasUnicas->count() === $cantidadRegistros;
    }

    /** Obtiene la recomendación operativa según el puntaje de estado de ánimo. */
    public function getRecomendacionEstadoAnimo(int $valor): ?string
    {
        if ($valor >= 10 && $valor <= 14) {
            return 'Contactar y preguntar si pudo agendar un control con su médico.';
        } elseif ($valor >= 15) {
            return 'Contactar y preguntar si pudo acceder a atención en emergencias/urgencias.';
        }

        return null;
    }

    /**
     * Guarda cuestionarios de estado de ánimo, calcula el puntaje y genera alertas si aplica.
     *
     * @param array<int, array<string, mixed>> $data
     */
    protected function saveDatosEstadoAnimo(int $idPaciente, array $data): void
    {
        foreach ($data as $d) {
            $valor = 0;
            for ($i = 1; $i <= 9; $i++) {
                $valor += (int) $d['pregunta_' . $i];
            }

            $registroEstadoAnimo = EstadoAnimo::updateOrCreate([
                'paciente_id' => $idPaciente,
                'id_app' => $d['id_estado_animo'],
            ], [
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

            $recomendacion = $this->getRecomendacionEstadoAnimo($valor);
            if ($registroEstadoAnimo->wasRecentlyCreated && $recomendacion !== null) {
                $recomendacion .= PHP_EOL . 'Completar formulario de alerta clínica.';

                $this->saveAlerta(
                    $idPaciente,
                    'clinica',
                    $registroEstadoAnimo->id,
                    'estado_animo',
                    'Estado de ánimo alterado',
                    $recomendacion,
                    now()->addDays(2)->format('Y-m-d')
                );
            }
        }
    }

    /** Crea una alerta clínica vinculada a un dato clínico concreto. */
    protected function saveAlerta(
        int $idPaciente,
        string $tipo,
        int $entidadId,
        string $entidad_tipo,
        string $descripcion,
        string $recomendacion,
        string $fechaContacto
    ): Alerta
    {
        return Alerta::create([
            'paciente_id' => $idPaciente,
            'tipo' => $tipo,
            'entidad_id' => $entidadId,
            'entidad_tipo' => $entidad_tipo,
            'descripcion' => $descripcion,
            'recomendacion' => $recomendacion,
            'fecha' => now(),
            'fecha_contacto' => $fechaContacto,
        ]);
    }

    /**
     * Guarda cuestionarios y sus respuestas, identificándolos por el id de la aplicación móvil.
     *
     * @param array<int, array<string, mixed>> $data
     */
    protected function saveDatosCuestionario(int $idPaciente, array $data): void
    {
        foreach ($data as $d) {
            $cuestionario = Cuestionario::updateOrCreate([
                'paciente_id' => $idPaciente,
                'id_app' => $d['id_quiz'],
            ], [
                'quiz_type' => $d['quiz_type'],
                'fecha_registro' => $d['fecha_registro'],
                'fecha_usuario' => $d['fecha_usuario'],
                'fecha_sincronizacion' => $d['fecha_sincronizacion'],
            ]);

            foreach ($d['preguntas'] as $p) {
                $cuestionario->preguntas()->updateOrCreate([
                    'pregunta_index' => $p['pregunta_index'],
                ], [
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

    /**
     * Guarda o actualiza las metas enviadas por la aplicación móvil.
     *
     * @param array<int, array<string, mixed>> $data
     */
    protected function saveDatosMetas(int $idPaciente, array $data): void
    {
        foreach ($data as $d) {
            Meta::updateOrCreate([
                'paciente_id' => $idPaciente,
                'id_app' => $d['id_meta'],
            ], [
                'semana' => $d['semana'],
                'sesion' => $d['sesion'],
                'tipo_actividad' => $d['tipo_actividad'],
                'actividad' => $d['actividad'],
                'lugar' => data_get($d, 'lugar'),
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

    /**
     * Guarda o actualiza los contenidos multimedia que el paciente marcó como leídos.
     *
     * @param array<int, array<string, mixed>> $data
     */
    protected function saveDatosMultimediaRead(int $idPaciente, array $data): void
    {
        foreach ($data as $d) {
            MultimediaRead::updateOrCreate([
                'paciente_id' => $idPaciente,
                'id_app' => $d['id_multimedia_read'],
            ], [
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
