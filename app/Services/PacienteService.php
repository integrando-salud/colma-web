<?php

namespace App\Services;

use App\Enums\MotivoInteraccion;
use App\Models\Diagnostico;
use App\Models\Entrenamiento;
use App\Models\Paciente;
use App\Models\User;
use App\Support\UserTimezone;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class PacienteService
{
    /**
     * Construye el listado de pacientes aplicando el alcance del usuario autenticado,
     * los filtros solicitados y el ordenamiento permitido.
     *
     * @param  array<string, mixed>  $filters
     */
    public function queryGetPacientes(array $filters = []): Builder
    {
        $usuario = auth()->user();
        $query = Paciente::query()
            ->select('pacientes.*')
            ->visibleTo($usuario);

        $query->withCount(['alertas AS numero_alertas' => function (Builder $query) {
            $query->whereNull('revisado');
        }]);

        $query->selectSub('
            SELECT MIN(fecha) FROM (
                SELECT (
                    SELECT i_programada.nueva_fecha_contacto
                    FROM interacciones i_programada
                    WHERE i_programada.paciente_id = pacientes.id
                        AND i_programada.nueva_fecha_contacto IS NOT NULL
                        AND i_programada.deleted_at IS NULL
                        AND (
                            i_programada.entrenamiento_id IS NULL
                            OR EXISTS (
                                SELECT 1
                                FROM entrenamientos entrenamiento_programado
                                WHERE entrenamiento_programado.id = i_programada.entrenamiento_id
                                    AND entrenamiento_programado.activo = 1
                            )
                        )
                        AND NOT EXISTS (
                            SELECT 1
                            FROM interacciones i_contactado
                            WHERE i_contactado.paciente_id = pacientes.id
                                AND i_contactado.contactado = 1
                                AND i_contactado.deleted_at IS NULL
                                AND i_contactado.fecha >= i_programada.nueva_fecha_contacto
                        )
                    ORDER BY i_programada.nueva_fecha_contacto
                    LIMIT 1
                ) AS fecha
                UNION ALL
                SELECT (
                    SELECT a.fecha_contacto
                    FROM alertas a
                    WHERE a.paciente_id = pacientes.id
                        AND a.fecha_contacto IS NOT NULL
                        AND a.revisado IS NULL
                        AND a.deleted_at IS NULL
                    ORDER BY a.fecha_contacto
                    LIMIT 1
                ) AS fecha
            ) proximas_fechas
        ', 'nueva_fecha_contacto');

        if (! empty($filters['nombre'])) {
            $query->where(function ($query) use ($filters) {
                $nombre = '%'.trim($filters['nombre']).'%';
                $nombreCompleto = DB::connection()->getDriverName() === 'sqlite'
                    ? "trim(pacientes.nombre || ' ' || pacientes.apellido)"
                    : "trim(concat(pacientes.nombre, ' ', pacientes.apellido))";

                $query->where('pacientes.nombre', 'like', $nombre)
                    ->orWhere('pacientes.apellido', 'like', $nombre)
                    ->orWhereRaw("{$nombreCompleto} like ?", [$nombre])
                    ->orWhere('pacientes.id_externo', trim($filters['nombre']));
            });
        }

        if (isset($filters['estado']) && is_numeric($filters['estado'])) {
            $query->where('activo', $filters['estado']);
        }

        if (! empty($filters['promotor'])) {
            $query->where('promotor_id', $filters['promotor']);
        }

        if (! empty($filters['diagnostico'])) {
            $query->whereHas('diagnosticos', function (Builder $query) use ($filters) {
                $query->where('diagnostico_id', $filters['diagnostico']);
            });
        }

        if (! empty($filters['alertas'])) {
            $query->whereHas('alertas', function (Builder $query) use ($filters) {
                $query->where('tipo', $filters['alertas'])
                    ->whereNull('revisado');
            });
        }

        $query->orderByDesc('pacientes.activo');
        $orderBy = data_get($filters, 'orderBy', 'id_externo');
        $orderBy = in_array($orderBy, ['id_externo', 'fecha_enrolamiento', 'ingresos', 'fecha_proximo_contacto', 'ultimo_ingreso', 'diagnostico', 'promotor'], true)
            ? $orderBy
            : 'id_externo';
        $orderDir = strtolower((string) data_get($filters, 'orderDir', 'desc')) === 'asc' ? 'asc' : 'desc';

        switch ($orderBy) {
            case 'ingresos':
                $query->leftJoin('users', 'pacientes.user_id', '=', 'users.id')
                    ->orderBy('users.accesos', $orderDir);
                break;
            case 'fecha_proximo_contacto':
                $query->orderByRaw("CASE WHEN nueva_fecha_contacto IS NULL THEN 1 ELSE 0 END, nueva_fecha_contacto $orderDir");
                break;
            case 'ultimo_ingreso':
                $query->leftJoin('users', 'pacientes.user_id', '=', 'users.id')
                    ->orderBy('users.ultimo_acceso', $orderDir);
                break;
            case 'diagnostico':
                $query->leftJoin('pacientes_diagnosticos', 'pacientes.id', '=', 'pacientes_diagnosticos.paciente_id')
                    ->leftJoin('diagnosticos', 'pacientes_diagnosticos.diagnostico_id', '=', 'diagnosticos.id')
                    ->orderBy('diagnosticos.nombre', $orderDir);
                break;
            case 'promotor':
                $query->leftJoin('users AS promotores', 'pacientes.promotor_id', '=', 'promotores.id')
                    ->orderBy('promotores.nombre', $orderDir);
                break;
            default:
                $query->orderBy("pacientes.$orderBy", $orderDir);
        }

        // El ID garantiza un orden total y evita que la paginación por offset
        // omita o repita pacientes entre solicitudes.
        $query->orderBy('pacientes.id', $orderDir);

        return $query;
    }

    /**
     * Crea o actualiza un paciente, su usuario de acceso y sus diagnósticos de
     * manera atómica. Las fechas de entrada deben estar en formato d/m/Y.
     *
     * @param  array<string, mixed>  $data
     * @throws Throwable
     */
    public function storePaciente(array $data): Paciente
    {
        return DB::transaction(function () use ($data): Paciente {
            $paciente = empty($data['id']) ? new Paciente : Paciente::findOrFail($data['id']);
            $paciente->id_externo = data_get($data, 'id_externo');
            $paciente->nombre = $data['nombre'];
            $paciente->apellido = $data['apellido'];
            $paciente->fecha_nacimiento = Carbon::createFromFormat('d/m/Y', $data['fecha_nacimiento'])->toDateString();
            $paciente->sexo = $data['sexo'];
            $paciente->documento = $data['documento'];
            $paciente->telefono = $data['telefono'];
            $paciente->telefono2 = data_get($data, 'telefono2');
            $paciente->email = $data['email'];
            $paciente->direccion = $data['direccion'];
            $paciente->referencia = data_get($data, 'referencia');
            $paciente->observaciones = data_get($data, 'observaciones');
            $paciente->centro_salud_id = data_get($data, 'centro_salud_id');
            $paciente->promotor_id = data_get($data, 'promotor_id');
            $paciente->fecha_enrolamiento = Carbon::createFromFormat('d/m/Y', $data['fecha_enrolamiento'])->toDateString();
            if (array_key_exists('test', $data)) {
                $paciente->test = (bool) $data['test'];
            }

            $password = data_get($data, 'password');
            if ($password) {
                $paciente->clave = Crypt::encryptString($password);
            }

            if (empty($data['id'])) {
                $usuario = $this->createUsuario($paciente, $password);
                $paciente->user_id = $usuario->id;
            } else {
                $this->updateUsuario($paciente, $password);
            }

            $paciente->save();
            $this->syncDiagnosticos($paciente, $data['diagnosticos'] ?? []);

            return $paciente;
        });
    }

    /** Actualiza ambas representaciones de la contraseña sin modificar otros datos. */
    public function updatePassword(Paciente $paciente, string $password): void
    {
        DB::transaction(function () use ($paciente, $password): void {
            $usuario = $paciente->usuario()->firstOrFail();
            $usuario->password = $password;
            $usuario->saveOrFail();

            $paciente->clave = Crypt::encryptString($password);
            $paciente->saveOrFail();
        });
    }

    /** Crea el usuario de acceso asociado a un paciente nuevo. */
    private function createUsuario(Paciente $paciente, string $password): User
    {
        $usuarioService = new UsuarioService;
        $data = $this->fillDataUsuario($paciente, $password);

        return $usuarioService->storeUsuario($data);
    }

    /** Actualiza los datos del usuario asociado sin reemplazar su contraseña vacía. */
    private function updateUsuario(Paciente $paciente, ?string $password = null): void
    {
        $usuarioService = new UsuarioService;
        $data = array_merge($this->fillDataUsuario($paciente, $password), ['id' => $paciente->usuario->id]);

        $usuarioService->storeUsuario($data);
    }

    /** Prepara los atributos del usuario a partir de los datos del paciente. */
    private function fillDataUsuario(Paciente $paciente, ?string $password = null): array
    {
        $data = [
            'rol' => 'paciente',
            'nombre' => "$paciente->nombre $paciente->apellido",
            'sexo' => "$paciente->sexo",
            'documento' => "$paciente->documento",
            'email' => $paciente->email,
            'pais_id' => $paciente->centroSalud?->pais_id ?? null,
            'centro_salud_id' => $paciente->centro_salud_id ?? null,
        ];

        if ($password) {
            $data['password'] = $password;
        }

        return $data;
    }

    /** Sincroniza la relación de diagnósticos del paciente. */
    private function syncDiagnosticos(Paciente $paciente, array $diagnosticos): void
    {
        $paciente->diagnosticos()->sync($diagnosticos);
    }

    /**
     * Devuelve en una única página las mediciones de glucosa y presión arterial.
     * Las fechas opcionales deben usar el formato d/m/Y.
     */
    public function getDatosClinicos(Paciente|int $paciente, int $cantidad = 10, ?string $fechaDesde = null, ?string $fechaHasta = null): LengthAwarePaginator
    {
        if (is_int($paciente)) {
            $paciente = Paciente::findOrFail($paciente);
        }

        $timezone = UserTimezone::forUser(auth()->user());
        $fechaDesde = $fechaDesde
            ? Carbon::createFromFormat('d/m/Y', $fechaDesde, $timezone)->startOfDay()->utc()
            : $paciente->fecha_enrolamiento->copy()->setTimezone($timezone)->startOfDay()->utc();
        $fechaHasta = $fechaHasta
            ? Carbon::createFromFormat('d/m/Y', $fechaHasta, $timezone)->endOfDay()->utc()
            : now();

        $glucosa = DB::table('glucosa')
            ->selectRaw("id, fecha_registro, fecha_usuario, valor_mgdl AS glucosa_valor, ayuno AS glucosa_ayuno,
                NULL AS presion_sistolica, NULL AS presion_diastolica, 'glucosa' AS tipo")
            ->where('paciente_id', $paciente->id)
            ->whereBetween('fecha_registro', [$fechaDesde, $fechaHasta]);

        $presion = DB::table('presion_arterial')
            ->selectRaw("id, fecha_registro, fecha_usuario, NULL AS glucosa_valor, NULL AS glucosa_ayuno,
                presion_sistolica, presion_diastolica, 'presion' AS tipo")
            ->where('paciente_id', $paciente->id)
            ->whereBetween('fecha_registro', [$fechaDesde, $fechaHasta]);

        $registros = DB::query()
            ->fromSub($glucosa->unionAll($presion), 'registros')
            ->selectRaw("fecha_usuario,
                glucosa_valor, glucosa_ayuno, presion_sistolica, presion_diastolica")
            ->orderByDesc('fecha_usuario')
            ->orderByDesc('tipo')
            ->orderByDesc('id')
            ->paginate(max(1, $cantidad));

        $registros->setCollection(
            $registros->getCollection()->map(fn (object $registro) => [
                ...((array) $registro),
                'fecha' => Carbon::parse($registro->fecha_usuario, 'UTC')
                    ->setTimezone($timezone)
                    ->format('d/m/Y h:i A'),
            ])
        );

        return $registros;
    }

    /** Alterna y persiste el estado activo de un paciente. */
    public function cambiarEstado(Paciente $paciente): bool
    {
        $paciente->activo = ! $paciente->activo;

        return $paciente->save();
    }

    /**
     * Obtiene las siguientes X fechas y motivos de contacto para un paciente
     *
     * @return array<int, array{fecha: string, motivo: string}>
     */
    public function getFechasProximosContactos(Paciente $paciente, int $cantidad = 3): array
    {
        $fechasContactoInteracciones = $paciente->relationLoaded('proximosContactosProgramados')
            ? $paciente->proximosContactosProgramados
            : $this->getFechasProximosContactosProgramados($paciente, $cantidad);
        $fechasContactoAlertas = $paciente->relationLoaded('proximosContactosAlertas')
            ? $paciente->proximosContactosAlertas
            : $this->getFechasProximosContactosAlertas($paciente, $cantidad);
        $fechas = collect()
            ->merge($fechasContactoInteracciones->toArray())
            ->merge($fechasContactoAlertas->toArray())
            ->sortBy('fecha')
            ->values()
            ->map(function ($fecha) use ($paciente) {
                $motivo = MotivoInteraccion::tryFrom($fecha['motivo'] ?? '');

                return [
                    'fecha' => date('d/m/Y', strtotime($fecha['fecha'])),
                    'motivo' => $this->getMotivoSiguienteContacto($paciente, $fecha, $motivo)
                        ?? $motivo?->etiquetaCorta()
                        ?? ($fecha['motivo'] ?? ''),
                ];
            })
            ->take($cantidad);

        return $fechas->toArray();
    }

    /**
     * Resuelve la etapa siguiente del circuito de entrenamiento para la fecha
     * programada. Los motivos ajenos al circuito conservan su etiqueta actual.
     *
     * @param array<string, mixed> $fecha
     */
    private function getMotivoSiguienteContacto(Paciente $paciente, array $fecha, ?MotivoInteraccion $motivo): ?string
    {
        if (! $motivo) {
            return null;
        }

        $cantidadSeguimientosMensuales = 0;

        if ($motivo === MotivoInteraccion::SEGUIMIENTO_MENSUAL) {
            $query = $paciente->interacciones()
                ->where('motivo', MotivoInteraccion::SEGUIMIENTO_MENSUAL);

            if ($fecha['entrenamiento_id'] ?? null) {
                $query->where('entrenamiento_id', $fecha['entrenamiento_id']);
            } else {
                $query->whereNull('entrenamiento_id');
            }

            if ($fecha['fecha_interaccion'] ?? null) {
                $query->where('fecha', '<=', $fecha['fecha_interaccion']);
            }

            $cantidadSeguimientosMensuales = $query->count();
        }

        $maximoSeguimientosMensuales = Entrenamiento::SEGUIMIENTOS_MENSUALES_POR_DEFECTO;

        if ($fecha['entrenamiento_id'] ?? null) {
            $maximoSeguimientosMensuales = Entrenamiento::query()
                ->whereKey($fecha['entrenamiento_id'])
                ->value('seguimientos_mensuales')
                ?? Entrenamiento::SEGUIMIENTOS_MENSUALES_POR_DEFECTO;
        }

        return $motivo->siguienteMotivoContacto($cantidadSeguimientosMensuales, $maximoSeguimientosMensuales);
    }

    /**
     * Precarga los datos que serializa PacienteResource para que las pantallas
     * de detalle no ejecuten consultas implícitas al transformar el modelo.
     */
    public function cargarRelacionesParaResource(Paciente $paciente): Paciente
    {
        $paciente->loadMissing([
            'centroSalud',
            'promotor',
            'usuario',
            'diagnosticos',
            'entrenamientos' => fn ($query) => $query
                ->where('activo', true)
                ->latest()
                ->with(['tratamientoVersion.sesiones.elementos', 'progresoElementos']),
            'proximosContactosProgramados' => fn ($query) => $query
                ->pendingAsNextContact()
                ->orderBy('nueva_fecha_contacto')
                ->limit(3)
                ->selectRaw('id, paciente_id, entrenamiento_id, motivo, fecha AS fecha_interaccion, nueva_fecha_contacto AS fecha'),
            'proximosContactosAlertas' => fn ($query) => $query
                ->whereNotNull('fecha_contacto')
                ->whereNull('revisado')
                ->orderBy('fecha_contacto')
                ->limit(3)
                ->selectRaw("id, paciente_id, fecha_contacto AS fecha, CASE
                    WHEN tipo = 'operativa' THEN 'Alerta operativa'
                    WHEN tipo = 'clinica' THEN 'Alerta clínica'
                    ELSE ''
                END AS motivo"),
        ]);

        if (! isset($paciente->numero_alertas)) {
            $paciente->loadCount(['alertas AS numero_alertas' => fn (Builder $query) => $query->whereNull('revisado')]);
        }

        return $paciente;
    }

    /**
     * Obtiene las fechas y motivos de contacto programados para un paciente
     *
     * @return mixed
     */
    public function getFechasProximosContactosProgramados(Paciente $paciente, int $cantidad = 3): Collection
    {
        return $paciente->interacciones()
            ->pendingAsNextContact()
            ->orderBy('nueva_fecha_contacto')
            ->limit($cantidad)
            ->get(['id', 'entrenamiento_id', 'motivo', 'fecha AS fecha_interaccion', 'nueva_fecha_contacto AS fecha']);
    }

    /**
     * Obtiene las fechas y motivos de contacto producto de alertas para un paciente
     *
     * @return mixed
     */
    public function getFechasProximosContactosAlertas(Paciente $paciente, int $cantidad = 3): Collection
    {
        return $paciente->alertas()
            ->whereNotNull('fecha_contacto')
            ->whereNull('revisado')
            ->orderBy('fecha_contacto')
            ->limit($cantidad)
            ->selectRaw("
                id, fecha_contacto AS fecha,
                CASE
                    WHEN tipo = 'operativa' THEN 'Alerta operativa'
                    WHEN tipo = 'clinica' THEN 'Alerta clínica'
                    ELSE ''
                END AS motivo
            ")
            ->get();
    }

    /**
     * Obtiene la fecha de contacto más próxima para un paciente
     */
    public function getFechaProximoContacto(Paciente $paciente): ?string
    {
        if ($paciente->relationLoaded('proximosContactosProgramados')
            && $paciente->relationLoaded('proximosContactosAlertas')) {
            return collect()
                ->merge($paciente->proximosContactosProgramados)
                ->merge($paciente->proximosContactosAlertas)
                ->pluck('fecha')
                ->filter()
                ->map(fn ($fecha) => Carbon::parse($fecha)->toDateString())
                ->min();
        }

        $fechaContactoInteracciones = $this->getFechaProximoContactoProgramado($paciente);
        $fechaContactoAlertas = $this->getFechaProximoContactoAlerta($paciente);

        return collect([$fechaContactoInteracciones, $fechaContactoAlertas])
            ->filter()
            ->map(fn ($fecha) => Carbon::parse($fecha)->toDateString())
            ->min();
    }

    /**
     * Obtiene la próxima fecha de contacto programada para un paciente
     * Si ya fue contactado con posterioridad a esa fecha, devuelve NULL
     */
    public function getFechaProximoContactoProgramado(Paciente $paciente): ?string
    {
        return $paciente->interacciones()
            ->pendingAsNextContact()
            ->orderBy('nueva_fecha_contacto')
            ->value('nueva_fecha_contacto');
    }

    /**
     * Obtiene la próxima fecha de contacto producto de una alerta para un paciente
     */
    public function getFechaProximoContactoAlerta(Paciente $paciente): ?string
    {
        return $paciente->alertas()
            ->whereNotNull('fecha_contacto')
            ->whereNull('revisado')
            ->orderBy('fecha_contacto')
            ->value('fecha_contacto');
    }

    /**
     * Obtiene el entrenamiento actual de un paciente
     */
    public function getEntrenamientoActual(Paciente $paciente): ?Entrenamiento
    {
        return $paciente->entrenamientos()->where('activo', true)->latest()->first();
    }

    /**
     * Resetea los datos de un paciente de prueba
     *
     * @throws Exception
     */
    public function resetearDatosPacienteTest(Paciente $paciente): void
    {
        if (! $paciente->test) {
            throw new Exception('No se pueden resetear los datos de un paciente que no es de prueba.', 500);
        }

        $paciente->glucosa()->delete();
        $paciente->presionArterial()->delete();
        $paciente->estadoAnimo()->delete();
        $paciente->interacciones()->delete();
        $paciente->alertas()->delete();
        $paciente->entrenamientos()->delete();
    }

    /**
     * Crea un paciente de prueba con un documento numérico de ocho dígitos.
     */
    public function crearPacienteTest(): Paciente|false
    {
        try {
            $sexo = fake()->randomElement(['M', 'F']);
            do {
                $documento = fake()->numerify('########');
            } while (
                Paciente::withTrashed()->where('documento', $documento)->exists()
                || User::withTrashed()->where('documento', $documento)->exists()
            );

            $nombre = $sexo === 'M'
                ? fake('es_ES')->name()
                : fake('es_ES')->firstNameFemale();
            $apellido = fake('es_ES')->lastName();
            $email = (Str::slug("$nombre $apellido $documento")).'@prueba.com';

            $data = [
                'nombre' => $nombre,
                'apellido' => $apellido,
                'fecha_nacimiento' => fake()->dateTimeBetween('-70 years', '-30 years')->format('d/m/Y'),
                'sexo' => $sexo,
                'documento' => $documento,
                'telefono' => fake('es_ES')->phoneNumber(),
                'email' => $email,
                'direccion' => fake('es_ES')->address(),
                'fecha_enrolamiento' => now()->format('d/m/Y'),
                'password' => $documento,
                'diagnosticos' => Diagnostico::where('activo', true)->pluck('id')->toArray(),
                'test' => true,
            ];

            return $this->storePaciente($data);
        } catch (Throwable $th) {
            Log::error($th);

            return false;
        }
    }
}
