<?php

namespace App\Services;

use App\Models\Diagnostico;
use App\Models\Interaccion;
use App\Models\Paciente;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class PacienteService
{
    public function queryGetPacientes(array $filters = [])
    {
        $usuario = auth()->user();
        $query = Paciente::query();

        if ($usuario->isA('promotor')) {
            $query->where('promotor_id', $usuario->id);
        } elseif ($usuario->isA('supervisor')) {
            $query->whereIn('promotor_id', $usuario->promotores->pluck('id'));
        } elseif ($usuario->isA('supervisor_pais')) {
            $query->whereHas('centroSalud', function (Builder $query) use ($usuario) {
                $query->where('pais_id', $usuario->pais_id);
            });
        }

        $query->withCount(['alertas AS numero_alertas' => function (Builder $query) {
            $query->whereNull('revisado');
        }]);

        $query->selectSub("
            SELECT MIN(fecha) FROM (
                SELECT (
                    SELECT CASE
                        WHEN EXISTS (
                            SELECT 1
                            FROM interacciones i_contactado
                            WHERE i_contactado.paciente_id = pacientes.id
                                AND i_contactado.contactado = 1
                                AND i_contactado.deleted_at IS NULL
                                AND i_contactado.fecha >= (
                                    SELECT i_programada.nueva_fecha_contacto
                                    FROM interacciones i_programada
                                    WHERE i_programada.paciente_id = pacientes.id
                                        AND i_programada.nueva_fecha_contacto IS NOT NULL
                                        AND i_programada.deleted_at IS NULL
                                    ORDER BY i_programada.nueva_fecha_contacto
                                    LIMIT 1
                                )
                        ) THEN NULL
                        ELSE (
                            SELECT i_programada.nueva_fecha_contacto
                            FROM interacciones i_programada
                            WHERE i_programada.paciente_id = pacientes.id
                                AND i_programada.nueva_fecha_contacto IS NOT NULL
                                AND i_programada.deleted_at IS NULL
                            ORDER BY i_programada.nueva_fecha_contacto
                            LIMIT 1
                        )
                    END
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
        ", 'nueva_fecha_contacto');

        if (!empty($filters['nombre'])) {
            $query->where(function ($query) use ($filters) {
                $nombre = '%' . $filters['nombre'] . '%';
                $query->where('nombre', 'like', $nombre)
                    ->orWhere('apellido', 'like', $nombre)
                    ->orWhere('id', $filters['nombre']);
            });
        }

        if (isset($filters['estado']) && is_numeric($filters['estado'])) {
            $query->where('activo', $filters['estado']);
        }

        if (!empty($filters['promotor'])) {
            $query->where('promotor_id', $filters['promotor']);
        }

        if (!empty($filters['diagnostico'])) {
            $query->whereHas('diagnosticos', function (Builder $query) use ($filters) {
                $query->where('diagnostico_id', $filters['diagnostico']);
            });
        }

        if (!empty($filters['alertas'])) {
            $query->whereHas('alertas', function (Builder $query) use ($filters) {
                $query->where('tipo', $filters['alertas'])
                    ->whereNull('revisado');
            });
        }

        $query->orderByDesc('pacientes.activo');
        $orderBy = data_get($filters, 'orderBy', 'id');
        $orderDir = data_get($filters, 'orderDir', 'desc');

        switch ($orderBy) {
            case 'sesiones':
                break;
            case 'ingresos':
                $query->select('pacientes.*')
                    ->leftJoin('users', 'pacientes.user_id', '=', 'users.id')
                    ->orderBy('users.accesos', $orderDir);
                break;
            case 'fecha_proximo_contacto':
                $query->orderByRaw("CASE WHEN nueva_fecha_contacto IS NULL THEN 1 ELSE 0 END, nueva_fecha_contacto $orderDir");
                break;
            case 'ultimo_ingreso':
                $query->select('pacientes.*')
                    ->leftJoin('users', 'pacientes.user_id', '=', 'users.id')
                    ->orderBy('users.ultimo_acceso', $orderDir);
                break;
            case 'diagnostico':
                $query->select('pacientes.*')
                    ->leftJoin('pacientes_diagnosticos', 'pacientes.id', '=', 'pacientes_diagnosticos.paciente_id')
                    ->leftJoin('diagnosticos', 'pacientes_diagnosticos.diagnostico_id', '=', 'diagnosticos.id')
                    ->orderBy('diagnosticos.nombre', $orderDir);
                break;
            case 'promotor':
                $query->select('pacientes.*')
                    ->leftJoin('users AS promotores', 'pacientes.promotor_id', '=', 'promotores.id')
                    ->orderBy('promotores.nombre', $orderDir);
                break;
            default:
                $query->orderBy($orderBy, $orderDir);
        }

        return $query;
    }

    public function storePaciente(array $data): Paciente
    {
        try {
            DB::beginTransaction();

            $paciente = empty($data['id']) ? new Paciente() : Paciente::find($data['id']);
            $paciente->nombre = $data['nombre'];
            $paciente->apellido = $data['apellido'];
            $paciente->fecha_nacimiento = implode('-', array_reverse(explode('/', $data['fecha_nacimiento'])));
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
            $paciente->fecha_enrolamiento = implode('-', array_reverse(explode('/', $data['fecha_enrolamiento'])));
            $paciente->clave = Crypt::encryptString($data['password']);
            $paciente->test = data_get($data, 'test', false);

            if (empty($data['id'])) {
                $usuario = $this->createUsuario($paciente, $data['password']);
                $paciente->user_id = $usuario->id;
            } else {
                $this->updateUsuario($paciente);
            }

            $paciente->save();
            $this->syncDiagnosticos($paciente, $data['diagnosticos']);
            DB::commit();

            return $paciente;
        } catch (Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    private function createUsuario(Paciente $paciente, string $password): User
    {
        $usuarioService = new UsuarioService();
        $data = $this->fillDataUsuario($paciente, $password);

        return $usuarioService->storeUsuario($data);
    }

    private function updateUsuario(Paciente $paciente)
    {
        $usuarioService = new UsuarioService();
        $data = array_merge($this->fillDataUsuario($paciente), ['id' => $paciente->usuario->id]);

        return $usuarioService->storeUsuario($data);
    }

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

    private function syncDiagnosticos(Paciente $paciente, array $diagnosticos)
    {
        $paciente->diagnosticos()->sync($diagnosticos);
    }

    public function getDatosClinicos(Paciente|int $paciente, $cantidad = 10, $fechaDesde = null, $fechaHasta = null)
    {
        if (is_int($paciente)) {
            $paciente = Paciente::find($paciente);
        }

        if (!$fechaDesde) {
            $fechaDesde = $paciente->fecha_enrolamiento;
        }

        if (!$fechaHasta) {
            $fechaHasta = now();
        }

        $cantidad = (int) $cantidad;

        return DB::select("
            WITH fecha_base AS (
                SELECT DATE(fecha_registro) AS fecha
                FROM glucosa
                WHERE paciente_id = :paciente_id_fechas_glucosa
                    AND fecha_registro BETWEEN :fecha_inicio_fechas_glucosa AND :fecha_fin_fechas_glucosa

                UNION

                SELECT DATE(fecha_registro) AS fecha
                FROM presion_arterial
                WHERE paciente_id = :paciente_id_fechas_presion
                    AND fecha_registro BETWEEN :fecha_inicio_fechas_presion AND :fecha_fin_fechas_presion
            ),
            fechas AS (
                SELECT fecha
                FROM fecha_base
                GROUP BY fecha
                ORDER BY fecha DESC
                LIMIT $cantidad
            ),
            ult_glucosa AS (
                SELECT * FROM (
                    SELECT
                        id, paciente_id, valor_mgdl, ayuno,
                        DATE(fecha_registro) AS fecha,
                        fecha_registro,
                        ROW_NUMBER() OVER (PARTITION BY DATE(fecha_registro) ORDER BY fecha_registro DESC) AS rn
                    FROM glucosa
                    WHERE paciente_id = :paciente_id_ultima_glucosa
                        AND fecha_registro BETWEEN :fecha_inicio_ultima_glucosa AND :fecha_fin_ultima_glucosa
                ) t
                WHERE rn = 1
            ),
            ult_presion AS (
                SELECT * FROM (
                    SELECT
                        id, paciente_id, presion_sistolica, presion_diastolica,
                        DATE(fecha_registro) AS fecha,
                        fecha_registro,
                        ROW_NUMBER() OVER (PARTITION BY DATE(fecha_registro) ORDER BY fecha_registro DESC) AS rn
                    FROM presion_arterial
                    WHERE paciente_id = :paciente_id_ultima_presion
                      AND fecha_registro BETWEEN :fecha_inicio_ultima_presion AND :fecha_fin_ultima_presion
                ) t
                WHERE rn = 1
            )
            SELECT
                DATE_FORMAT(f.fecha, '%d/%m/%Y') AS fecha,
                g.valor_mgdl AS glucosa_valor,
                g.ayuno AS glucosa_ayuno,
                p.presion_sistolica,
                p.presion_diastolica
            FROM fechas f
            LEFT JOIN ult_glucosa g ON g.fecha = f.fecha
            LEFT JOIN ult_presion p ON p.fecha = f.fecha
            ORDER BY f.fecha ASC
        ", [
            'paciente_id_fechas_glucosa' => $paciente->id,
            'fecha_inicio_fechas_glucosa' => $fechaDesde,
            'fecha_fin_fechas_glucosa' => $fechaHasta,
            'paciente_id_fechas_presion' => $paciente->id,
            'fecha_inicio_fechas_presion' => $fechaDesde,
            'fecha_fin_fechas_presion' => $fechaHasta,
            'paciente_id_ultima_glucosa' => $paciente->id,
            'fecha_inicio_ultima_glucosa' => $fechaDesde,
            'fecha_fin_ultima_glucosa' => $fechaHasta,
            'paciente_id_ultima_presion' => $paciente->id,
            'fecha_inicio_ultima_presion' => $fechaDesde,
            'fecha_fin_ultima_presion' => $fechaHasta,
        ]);
    }

    public function cambiarEstado(Paciente $paciente): bool
    {
        $paciente->activo = !$paciente->activo;

        return $paciente->save();
    }

    /**
     * Obtiene las siguientes X fechas y motivos de contacto para un paciente
     *
     * @param Paciente $paciente
     * @param int $cantidad
     * @return array
     */
    public function getFechasProximosContactos(Paciente $paciente, int $cantidad = 3): array
    {
        $fechasContactoInteracciones = $this->getFechasProximosContactosProgramados($paciente);
        $fechasContactoAlertas = $this->getFechasProximosContactosAlertas($paciente);
        $fechas = collect()
            ->merge($fechasContactoInteracciones->toArray())
            ->merge($fechasContactoAlertas->toArray())
            ->sortBy('fecha')
            ->values()
            ->map(function ($fecha) {
                return [
                    'fecha' => date('d/m/Y', strtotime($fecha['fecha'])),
                    'motivo' => data_get(Interaccion::MOTIVOS_CONTACTO_CORTOS, $fecha['motivo'], $fecha['motivo'] ?? ''),
                ];
            })
            ->take($cantidad);

        return $fechas->toArray();
    }

    /**
     * Obtiene las fechas y motivos de contacto programados para un paciente
     *
     * @param Paciente $paciente
     * @return mixed
     */
    public function getFechasProximosContactosProgramados(Paciente $paciente): mixed
    {
        return $paciente->interacciones()
            ->whereNotNull('nueva_fecha_contacto')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('interacciones AS subquery')
                    ->whereRaw('interacciones.nueva_fecha_contacto < subquery.fecha')
                    ->whereColumn('interacciones.paciente_id', 'subquery.paciente_id');
            })
            ->orderBy('nueva_fecha_contacto')
            ->get(['motivo', 'nueva_fecha_contacto AS fecha']);
    }

    /**
     * Obtiene las fechas y motivos de contacto producto de alertas para un paciente
     *
     * @param Paciente $paciente
     * @return mixed
     */
    public function getFechasProximosContactosAlertas(Paciente $paciente): mixed
    {
        return $paciente->alertas()
            ->whereNotNull('fecha_contacto')
            ->whereNull('revisado')
            ->orderBy('fecha_contacto')
            ->selectRaw("
                fecha_contacto AS fecha,
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
     *
     * @param Paciente $paciente
     * @return mixed
     */
    public function getFechaProximoContacto(Paciente $paciente): mixed
    {
        $fechaContactoInteracciones = $this->getFechaProximoContactoProgramado($paciente);
        $fechaContactoAlertas = $this->getFechaProximoContactoAlerta($paciente);

        if (is_null($fechaContactoInteracciones)) {
            return $fechaContactoAlertas;
        }

        if (is_null($fechaContactoAlertas)) {
            return $fechaContactoInteracciones;
        }

        return strtotime($fechaContactoInteracciones) <= strtotime($fechaContactoAlertas)
            ? $fechaContactoInteracciones
            : $fechaContactoAlertas;
    }

    /**
     * Obtiene la próxima fecha de contacto programada para un paciente
     * Si ya fue contactado con posterioridad a esa fecha, devuelve NULL
     *
     * @param Paciente $paciente
     * @return mixed|null
     */
    public function getFechaProximoContactoProgramado(Paciente $paciente): mixed
    {
        $fechaProximoContactoProgramado = $paciente->interacciones()
            ->whereNotNull('nueva_fecha_contacto')
            ->orderBy('nueva_fecha_contacto', 'desc')
            ->value('nueva_fecha_contacto');

        if ($fechaProximoContactoProgramado) {
            $contactado = $paciente->interacciones()
                ->where('contactado', true)
                ->where('fecha', '>=', $fechaProximoContactoProgramado)
                ->first();

            return $contactado ? null : $fechaProximoContactoProgramado;
        }

        return null;
    }

    /**
     * Obtiene la próxima fecha de contacto producto de una alerta para un paciente
     *
     * @param Paciente $paciente
     * @return mixed
     */
    public function getFechaProximoContactoAlerta(Paciente $paciente): mixed
    {
        return $paciente->alertas()
            ->whereNotNull('fecha_contacto')
            ->whereNull('revisado')
            ->orderBy('fecha_contacto')
            ->value('fecha_contacto');
    }

    /**
     * Obtiene el entrenamiento actual de un paciente
     *
     * @param Paciente $paciente
     * @return mixed
     */
    public function getEntrenamientoActual(Paciente $paciente): mixed
    {
        return $paciente->entrenamientos()->where('activo', true)->latest()->first();
    }

    /**
     * Resetea los datos de un paciente de prueba
     *
     * @param Paciente $paciente
     * @return void
     * @throws Exception
     */
    public function resetearDatosPacienteTest(Paciente $paciente): void
    {
        if (!$paciente->test) {
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
     * Crea un paciente de prueba
     *
     * @return Paciente|false
     */
    public function crearPacienteTest(): Paciente|false
    {
        try {
            $sexo = fake()->randomElement(['M', 'F']);
            $ultimoDocumento = Paciente::where('test', true)
                ->latest()
                ->value('documento');

            if ($ultimoDocumento) {
                [$test, $numero] = explode('-', $ultimoDocumento);
                $numero++;
                $documento = "$test-$numero";
            } else {
                $documento = 'test-1';
            }

            $nombre = $sexo === 'M'
                ? fake('es_ES')->name()
                : fake('es_ES')->firstNameFemale();
            $apellido = fake('es_ES')->lastName();
            $email = (Str::slug("$nombre $apellido $documento")) . '@prueba.com';

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
