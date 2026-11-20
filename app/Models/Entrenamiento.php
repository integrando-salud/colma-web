<?php

namespace App\Models;

use App\Enums\MotivoInteraccion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class Entrenamiento extends Model
{
    protected $fillable = [
        'paciente_id',
        'tratamiento_version_id',
        'fecha',
        'seguimientos_mensuales',
        'seguimiento_dos_semanas_inicio',
        'seguimiento_dos_semanas_fin',
        'llamada_mensual_1_inicio',
        'llamada_mensual_1_fin',
        'llamada_mensual_2_inicio',
        'llamada_mensual_2_fin',
        'llamada_mensual_3_inicio',
        'llamada_mensual_3_fin',
        'llamada_mensual_4_inicio',
        'llamada_mensual_4_fin',
        'llamada_mensual_5_inicio',
        'llamada_mensual_5_fin',
        'agendar_cita_final_inicio',
        'agendar_cita_final_fin',
        'activo',
        'user_id',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'seguimientos_mensuales' => 'integer',
        'fecha' => 'date',
        'seguimiento_dos_semanas_inicio' => 'date',
        'seguimiento_dos_semanas_fin' => 'date',
        'llamada_mensual_1_inicio' => 'date',
        'llamada_mensual_1_fin' => 'date',
        'llamada_mensual_2_inicio' => 'date',
        'llamada_mensual_2_fin' => 'date',
        'llamada_mensual_3_inicio' => 'date',
        'llamada_mensual_3_fin' => 'date',
        'llamada_mensual_4_inicio' => 'date',
        'llamada_mensual_4_fin' => 'date',
        'llamada_mensual_5_inicio' => 'date',
        'llamada_mensual_5_fin' => 'date',
        'agendar_cita_final_inicio' => 'date',
        'agendar_cita_final_fin' => 'date',
    ];

    const array DIAS_SUMA = [
        'seguimiento_dos_semanas_inicio' => 14,
        'seguimiento_dos_semanas_fin' => 21,
        'llamada_mensual_1_inicio' => 28,
        'llamada_mensual_1_fin' => 42,
        'llamada_mensual_2_inicio' => 56,
        'llamada_mensual_2_fin' => 70,
        'llamada_mensual_3_inicio' => 84,
        'llamada_mensual_3_fin' => 98,
        'llamada_mensual_4_inicio' => 112,
        'llamada_mensual_4_fin' => 126,
        'llamada_mensual_5_inicio' => 140,
        'llamada_mensual_5_fin' => 154,
        'agendar_cita_final_inicio' => 168,
        'agendar_cita_final_fin' => 182,
    ];

    public const int SEGUIMIENTOS_MENSUALES_POR_DEFECTO = 5;

    /**
     * Devuelve los días del cronograma. La cita final se agenda un mes después
     * del último seguimiento mensual, independientemente de si el circuito es
     * de tres o cinco seguimientos.
     *
     * @return array<string, int>
     */
    public static function diasSuma(int $seguimientosMensuales): array
    {
        $diasSuma = self::DIAS_SUMA;
        $diasSuma['agendar_cita_final_inicio'] = 28 * ($seguimientosMensuales + 1);
        $diasSuma['agendar_cita_final_fin'] = (28 * ($seguimientosMensuales + 1)) + 14;

        return $diasSuma;
    }

    public const int ETAPA_DOS_SEMANAS = 0;

    public const int ETAPA_AGENDAR_CITA_FINAL = 6;

    public const array VENTANAS = [
        self::ETAPA_DOS_SEMANAS => 'seguimiento_dos_semanas',
        1 => 'llamada_mensual_1',
        2 => 'llamada_mensual_2',
        3 => 'llamada_mensual_3',
        4 => 'llamada_mensual_4',
        5 => 'llamada_mensual_5',
        self::ETAPA_AGENDAR_CITA_FINAL => 'agendar_cita_final',
    ];

    public static function motivoEtapa(int $etapa): MotivoInteraccion
    {
        return match ($etapa) {
            self::ETAPA_DOS_SEMANAS => MotivoInteraccion::SEGUIMIENTO_DOS_SEMANAS,
            self::ETAPA_AGENDAR_CITA_FINAL => MotivoInteraccion::AGENDAR_CITA_FINAL,
            default => MotivoInteraccion::SEGUIMIENTO_MENSUAL,
        };
    }

    public static function queryEtapasPendientes(?int $entrenamientoId = null, ?int $excluirInteraccionId = null): Builder
    {
        $actual = DB::table('entrenamientos AS candidato')
            ->select('candidato.id')
            ->whereColumn('candidato.paciente_id', 'e.paciente_id')
            ->where('candidato.activo', true)
            ->orderByDesc('candidato.created_at')
            ->orderByDesc('candidato.id')
            ->limit(1);

        $etapas = null;

        foreach (self::VENTANAS as $etapa => $ventana) {
            $query = DB::table('entrenamientos AS e')
                ->selectRaw("e.id AS entrenamiento_id, e.paciente_id, ? AS motivo, $etapa AS etapa, e.{$ventana}_inicio AS fecha", [
                    self::motivoEtapa($etapa)->value,
                ])
                ->where('e.activo', true)
                ->where('e.id', $actual)
                ->whereColumn("e.{$ventana}_inicio", '>', 'e.fecha')
                ->when($entrenamientoId !== null, fn (Builder $query) => $query->where('e.id', $entrenamientoId))
                ->whereNotExists(function (Builder $query) use ($etapa, $excluirInteraccionId) {
                    $query->selectRaw('1')
                        ->from('interacciones AS realizada')
                        ->whereColumn('realizada.seguimiento_entrenamiento_id', 'e.id')
                        ->whereColumn('realizada.paciente_id', 'e.paciente_id')
                        ->where('realizada.etapa_seguimiento', $etapa)
                        ->where('realizada.motivo', self::motivoEtapa($etapa)->value)
                        ->where('realizada.contactado', true)
                        ->whereNull('realizada.deleted_at')
                        ->when($excluirInteraccionId !== null, fn (Builder $query) => $query->where('realizada.id', '!=', $excluirInteraccionId));
                });

            if (self::motivoEtapa($etapa) === MotivoInteraccion::SEGUIMIENTO_MENSUAL) {
                $query->where('e.seguimientos_mensuales', '>=', $etapa);
            }

            $etapas = $etapas ? $etapas->unionAll($query) : $query;
        }

        return DB::query()->fromSub($etapas, 'etapas');
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tratamientoVersion()
    {
        return $this->belongsTo(TratamientoVersion::class);
    }

    public function progresoElementos()
    {
        return $this->hasMany(ProgresoElemento::class);
    }

    public function metas()
    {
        return $this->hasMany(Meta::class);
    }

    public function respuestasQuiz()
    {
        return $this->hasMany(RespuestaQuiz::class);
    }
}
