<?php

namespace App\Models;

use App\Enums\MotivoInteraccion;
use App\Enums\MotivoReprogramacion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class Interaccion extends Model
{
    use SoftDeletes;

    public const array MODALIDADES = [
        'Llamada por teléfono / WhatsApp',
        'Encuentro presencial / en persona',
    ];

    public const array DESCRIPCION_CONTACTO = [
        0 => 'No contactado',
        1 => 'Contactado',
    ];

    protected $table = 'interacciones';

    protected $fillable = [
        'paciente_id',
        'user_id',
        'updated_by_user_id',
        'alerta_id',
        'entrenamiento_id',
        'seguimiento_entrenamiento_id',
        'etapa_seguimiento',
        'contactado',
        'fecha',
        'hora',
        'modalidad',
        'motivo',
        'observaciones',
        'motivo_reprogramacion',
        'fecha_cita',
        'hora_cita',
        'lugar_cita',
        'lugar_cita_otro',
        'fecha_cita_final',
        'hora_cita_final',
        'seguimiento_pregunta_a',
        'seguimiento_pregunta_b',
        'seguimiento_pregunta_c',
        'seguimiento_pregunta_d',
        'seguimiento_pregunta_e',
        'fecha_reentrenamiento',
        'hora_reentrenamiento',
        'lugar_reentrenamiento',
        'lugar_reentrenamiento_otro',
        'razon_contacto_participante',
        'razon_contacto_participante_otro',
        'contacto_participante_acciones',
        'nueva_fecha_contacto',
        'no_adherencia_pregunta_a',
        'no_adherencia_otro',
        'no_adherencia_pregunta_b',
        'no_adherencia_pregunta_c',
        'problemas_conexion_pregunta_a',
        'problemas_conexion_otro',
        'problemas_conexion_pregunta_b',
        'problemas_conexion_pregunta_c',
        'alerta_clinica_pregunta_a',
        'alerta_clinica_pregunta_b',
        'alerta_clinica_pregunta_c',
        'alerta_clinica_pregunta_d',
        'alerta_clinica_pregunta_e',
        'alerta_clinica_pregunta_f',
        'pedido_ayuda_pregunta_a',
        'pedido_ayuda_pregunta_b',
        'pedido_ayuda_pregunta_c',
        'pedido_ayuda_pregunta_d',
    ];

    protected $casts = [
        'contactado' => 'boolean',
        'seguimiento_entrenamiento_id' => 'integer',
        'etapa_seguimiento' => 'integer',
        'motivo' => MotivoInteraccion::class,
        'motivo_reprogramacion' => MotivoReprogramacion::class,
        'fecha' => 'datetime',
        'hora' => 'datetime',
        'fecha_cita' => 'datetime',
        'hora_cita' => 'datetime',
        'fecha_cita_final' => 'datetime',
        'hora_cita_final' => 'datetime',
        'fecha_reentrenamiento' => 'datetime',
        'hora_reentrenamiento' => 'datetime',
        'nueva_fecha_contacto' => 'datetime',
        'no_adherencia_pregunta_a' => 'array',
        'no_adherencia_pregunta_c' => 'datetime',
        'problemas_conexion_pregunta_a' => 'array',
        'problemas_conexion_pregunta_c' => 'datetime',
        'pedido_ayuda_pregunta_a' => 'array',
        'pedido_ayuda_pregunta_d' => 'datetime',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /** Usuario que realizó la última edición de la interacción. */
    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'updated_by_user_id')->withTrashed();
    }

    public function alerta()
    {
        return $this->belongsTo(Alerta::class);
    }

    /** Entrenamiento generado al registrar esta cita como realizada. */
    public function entrenamiento()
    {
        return $this->belongsTo(Entrenamiento::class);
    }

    /**
     * Excluye la programación que replica una alerta aún pendiente.
     *
     * Al no contactar una alerta se conserva la nueva fecha en la interacción
     * para su historial y también en la alerta para que pueda seguir siendo
     * gestionada. Para los listados de próximos contactos ambas referencias
     * representan el mismo evento, por lo que debe prevalecer la alerta.
     *
     * @param  Builder<Interaccion>  $query
     * @return Builder<Interaccion>
     */
    public function scopeWithoutDuplicatedPendingAlertSchedule(Builder $query): Builder
    {
        return $query->whereNotExists(function ($subquery) {
            $subquery->selectRaw('1')
                ->from('alertas AS alerta_programada')
                ->whereColumn('alerta_programada.id', 'interacciones.alerta_id')
                ->whereColumn('alerta_programada.paciente_id', 'interacciones.paciente_id')
                ->whereColumn('alerta_programada.fecha_contacto', 'interacciones.nueva_fecha_contacto')
                ->whereNull('alerta_programada.revisado')
                ->whereNull('alerta_programada.deleted_at');
        });
    }

    public static function queryContactosProgramados(): QueryBuilder
    {
        $cronograma = Entrenamiento::queryEtapasPendientes()
            ->selectRaw('etapas.*, ROW_NUMBER() OVER (PARTITION BY entrenamiento_id ORDER BY etapa) AS posicion');

        $legacy = static::query()->pendingAsNextContact()
            ->selectRaw('id, paciente_id, entrenamiento_id, motivo, fecha AS fecha_interaccion, nueva_fecha_contacto AS fecha, NULL AS etapa')
            ->toBase();

        return DB::query()->fromSub($cronograma, 'cronograma')
            ->where('posicion', 1)
            ->selectRaw('entrenamiento_id AS id, paciente_id, entrenamiento_id, motivo, NULL AS fecha_interaccion, fecha, etapa')
            ->unionAll($legacy);
    }

    /**
     * Programaciones independientes del cronograma y citas finales ya agendadas.
     *
     * @param  Builder<Interaccion>  $query
     * @return Builder<Interaccion>
     */
    public function scopePendingAsNextContact(Builder $query): Builder
    {
        return $query
            ->whereNotNull('nueva_fecha_contacto')
            ->where(function (Builder $query) {
                $query->whereNull('seguimiento_entrenamiento_id')
                    ->orWhere(function (Builder $query) {
                        $query->where('motivo', MotivoInteraccion::AGENDAR_CITA_FINAL)
                            ->where('contactado', true)
                            ->whereHas('paciente.entrenamientos', fn (Builder $query) => $query
                                ->whereColumn('entrenamientos.id', 'interacciones.seguimiento_entrenamiento_id')
                                ->where('entrenamientos.activo', true));
                    });
            })
            ->whereNotExists(function ($subquery) {
                $subquery->selectRaw('1')
                    ->from('entrenamientos AS cronograma')
                    ->whereColumn('cronograma.paciente_id', 'interacciones.paciente_id')
                    ->where(function ($query) {
                        $query->whereColumn('cronograma.id', 'interacciones.entrenamiento_id')
                            ->orWhere(function ($query) {
                                $query->whereNull('interacciones.entrenamiento_id')
                                    ->whereColumn('cronograma.fecha', 'interacciones.fecha');
                            });
                    })
                    ->whereColumn('cronograma.seguimiento_dos_semanas_inicio', '>', 'cronograma.fecha')
                    ->where('interacciones.motivo', MotivoInteraccion::CITA_ENTRENAMIENTO)
                    ->where('interacciones.contactado', true);
            })
            ->where(function (Builder $query) {
                $query->whereNull('entrenamiento_id')
                    ->orWhereHas('entrenamiento', fn (Builder $query) => $query->where('activo', true));
            })
            ->withoutDuplicatedPendingAlertSchedule()
            ->whereNotExists(function ($subquery) {
                $subquery->selectRaw('1')
                    ->from('interacciones AS contactada')
                    ->whereColumn('interacciones.paciente_id', 'contactada.paciente_id')
                    ->where('contactada.contactado', true)
                    ->whereColumn('contactada.fecha', '>=', 'interacciones.nueva_fecha_contacto')
                    ->whereNull('contactada.deleted_at');
            });
    }
}
