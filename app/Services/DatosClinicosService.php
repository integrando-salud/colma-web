<?php

namespace App\Services;

use App\Enums\AdvertenciaClinica;
use App\Models\Alerta;
use App\Models\EstadoAnimo;
use App\Models\Glucosa;
use App\Models\Meta;
use App\Models\Entrenamiento;
use App\Models\ProgresoElemento;
use App\Models\TratamientoElemento;
use App\Models\PresionArterial;
use App\Models\RespuestaQuiz;
use App\Models\User;
use App\Support\UserTimezone;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
        $usuario = User::query()->with('pais')->findOrFail($data['userId']);
        $paciente = $usuario->paciente()->firstOrFail();
        $idPaciente = $paciente->id;
        $data = $this->normalizarFechasUtc($data, UserTimezone::forUser($usuario));

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

            $idEntrenamiento = null;
            if (!empty($data['quiz']) || !empty($data['metas']) || !empty($data['progreso_elementos'])) {
                // Se conserva el mismo criterio de negocio usado para obtener el
                // entrenamiento actual: el último entrenamiento activo del paciente.
                $idEntrenamiento = $paciente->entrenamientos()
                    ->where('activo', true)
                    ->latest()
                    ->value('id');
            }

            if (!empty($data['quiz'])) {
                $this->saveRespuestasQuiz($idPaciente, $idEntrenamiento, $data['quiz']);
            }

            if (!empty($data['metas'])) {
                $this->saveDatosMetas($idPaciente, $idEntrenamiento, $data['metas']);
            }

            if (!empty($data['progreso_elementos'])) {
                $this->saveProgresoElementos($idPaciente, $idEntrenamiento, $data['progreso_elementos']);
            }

            $paciente->update([
                'fecha_ultima_sincronizacion' => now(),
            ]);

            return true;
        });
    }

    /**
     * La app móvil envía fechas sin offset. Se interpretan en el país del
     * paciente y se persisten como instantes UTC.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizarFechasUtc(array $data, string $timezone): array
    {
        $campos = [
            'fecha_registro',
            'fecha_usuario',
            'fecha_sincronizacion',
            'fecha_edicion',
            'fecha_objetivo',
            'fecha_estado',
        ];

        array_walk_recursive($data, function (mixed &$value, string $key) use ($campos, $timezone): void {
            if (! in_array($key, $campos, true) || ! is_string($value) || $value === '') {
                return;
            }

            $value = Carbon::createFromFormat('Y-m-d H:i:s', $value, $timezone)
                ->utc()
                ->format('Y-m-d H:i:s');
        });

        return $data;
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
                    'Estado de ánimo',
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
     * Guarda o actualiza las metas enviadas por la aplicación móvil.
     *
     * @param array<int, array<string, mixed>> $data
     */
    protected function saveDatosMetas(int $idPaciente, ?int $idEntrenamiento, array $data): void
    {
        $elementos = $this->elementosDelEntrenamiento($idEntrenamiento, $data, 'metas', 'METAS');

        foreach ($data as $d) {
            $meta = Meta::query()->where([
                'paciente_id' => $idPaciente,
                'entrenamiento_id' => $idEntrenamiento,
                'tratamiento_elemento_id' => $d['tratamiento_elemento_id'],
                'id_meta' => $d['id_meta'],
            ])->first();
            $fechaEstado = $d['fecha_edicion'] ?? $d['fecha_usuario'];

            if ($meta && $fechaEstado <= ($meta->fecha_edicion ?? $meta->fecha_usuario)->format('Y-m-d H:i:s')) {
                continue;
            }

            $atributos = [
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
            ];

            if ($meta) {
                $meta->update($atributos);
                continue;
            }

            Meta::create([
                'paciente_id' => $idPaciente,
                'entrenamiento_id' => $idEntrenamiento,
                'tratamiento_elemento_id' => $elementos->get($d['tratamiento_elemento_id'])->id,
                'id_meta' => $d['id_meta'],
                ...$atributos,
            ]);
        }
    }

    /**
     * Persiste la respuesta funcional de cada bloque QUIZ sin alterar su
     * progreso pedagógico. El JSON conserva el payload de preguntas completo.
     *
     * @param array<int, array<string, mixed>> $data
     */
    protected function saveRespuestasQuiz(int $idPaciente, ?int $idEntrenamiento, array $data): void
    {
        $elementos = $this->elementosDelEntrenamiento($idEntrenamiento, $data, 'quiz', 'QUIZ');

        foreach ($data as $dato) {
            $respuesta = RespuestaQuiz::query()->where([
                'paciente_id' => $idPaciente,
                'entrenamiento_id' => $idEntrenamiento,
                'tratamiento_elemento_id' => $dato['tratamiento_elemento_id'],
            ])->first();

            if ($respuesta && $dato['fecha_usuario'] <= $respuesta->fecha_usuario->format('Y-m-d H:i:s')) {
                continue;
            }

            $atributos = [
                'preguntas' => $dato['preguntas'],
                'fecha_registro' => $dato['fecha_registro'],
                'fecha_usuario' => $dato['fecha_usuario'],
                'fecha_sincronizacion' => $dato['fecha_sincronizacion'],
            ];

            if ($respuesta) {
                $respuesta->update($atributos);
                continue;
            }

            RespuestaQuiz::create([
                'paciente_id' => $idPaciente,
                'entrenamiento_id' => $idEntrenamiento,
                'tratamiento_elemento_id' => $elementos->get($dato['tratamiento_elemento_id'])->id,
                ...$atributos,
            ]);
        }
    }

    /**
     * Resuelve y valida elementos de un único tipo para el entrenamiento activo.
     *
     * @param array<int, array<string, mixed>> $data
     * @return Collection<int, TratamientoElemento>
     */
    private function elementosDelEntrenamiento(?int $idEntrenamiento, array $data, string $campo, string $tipo): Collection
    {
        if (! $idEntrenamiento) {
            throw ValidationException::withMessages([
                $campo => 'El paciente no tiene un entrenamiento activo para asociar los datos funcionales.',
            ]);
        }

        $entrenamiento = Entrenamiento::query()->findOrFail($idEntrenamiento);
        $ids = collect($data)->pluck('tratamiento_elemento_id')->unique();
        $elementos = TratamientoElemento::query()
            ->with('sesion.version')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        foreach ($data as $dato) {
            $elemento = $elementos->get($dato['tratamiento_elemento_id']);
            if (! $elemento
                || $elemento->tipo !== $tipo
                || $elemento->sesion->tratamiento_version_id !== $entrenamiento->tratamiento_version_id) {
                throw ValidationException::withMessages([
                    $campo => "El elemento debe ser $tipo y pertenecer al entrenamiento activo del paciente.",
                ]);
            }
        }

        return $elementos;
    }

    /**
     * Sincroniza el estado genérico de los elementos que la aplicación marcó.
     * El tipo, tratamiento, semana y sesión se derivan del elemento persistido.
     *
     * @param array<int, array<string, mixed>> $data
     */
    protected function saveProgresoElementos(int $idPaciente, ?int $idEntrenamiento, array $data): void
    {
        if (! $idEntrenamiento) {
            throw ValidationException::withMessages([
                'progreso_elementos' => 'El paciente no tiene un entrenamiento activo para asociar el progreso.',
            ]);
        }

        $entrenamiento = Entrenamiento::query()->findOrFail($idEntrenamiento);
        $ids = collect($data)->pluck('tratamiento_elemento_id')->unique();
        $elementos = TratamientoElemento::query()
            ->with('sesion.version')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        // Se valida el lote completo antes de efectuar cambios para conservar
        // la atomicidad de la sincronización.
        foreach ($data as $dato) {
            $elemento = $elementos->get($dato['tratamiento_elemento_id']);
            if (! $elemento
                || ! in_array($elemento->tipo, ['DESCRIPTION', 'VIDEO', 'QUIZ', 'METAS'], true)
                || $elemento->sesion->tratamiento_version_id !== $entrenamiento->tratamiento_version_id) {
                throw ValidationException::withMessages([
                    'progreso_elementos' => 'El elemento no pertenece al entrenamiento activo del paciente.',
                ]);
            }
        }

        foreach ($data as $dato) {
            $elementoId = $dato['tratamiento_elemento_id'];
            $porContexto = ProgresoElemento::query()
                ->where('paciente_id', $idPaciente)
                ->where('entrenamiento_id', $idEntrenamiento)
                ->where('tratamiento_elemento_id', $elementoId)
                ->first();

            $progreso = $porContexto;
            if (! $progreso) {
                ProgresoElemento::create([
                    'paciente_id' => $idPaciente,
                    'entrenamiento_id' => $idEntrenamiento,
                    'tratamiento_elemento_id' => $elementoId,
                    'completado' => $dato['completado'],
                    'fecha_estado' => $dato['fecha_estado'],
                    'fecha_sincronizacion' => $dato['fecha_sincronizacion'],
                ]);

                continue;
            }

            // Los reintentos y eventos recibidos fuera de orden no reemplazan
            // un estado conocido más reciente del mismo elemento.
            if ($dato['fecha_estado'] <= $progreso->fecha_estado->format('Y-m-d H:i:s')) {
                continue;
            }

            $progreso->update([
                'completado' => $dato['completado'],
                'fecha_estado' => $dato['fecha_estado'],
                'fecha_sincronizacion' => $dato['fecha_sincronizacion'],
            ]);
        }
    }
}
