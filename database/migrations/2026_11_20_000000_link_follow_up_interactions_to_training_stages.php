<?php

use App\Enums\MotivoInteraccion;
use App\Models\Entrenamiento;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interacciones', function (Blueprint $table) {
            $table->foreignId('seguimiento_entrenamiento_id')->nullable()
                ->constrained('entrenamientos')->nullOnDelete();
            $table->unsignedTinyInteger('etapa_seguimiento')->nullable();
            $table->index(['seguimiento_entrenamiento_id', 'etapa_seguimiento'], 'interacciones_etapa_seguimiento_index');
        });

        DB::table('interacciones')
            ->whereNull('deleted_at')
            ->whereIn('motivo', [
                MotivoInteraccion::SEGUIMIENTO_DOS_SEMANAS->value,
                MotivoInteraccion::SEGUIMIENTO_MENSUAL->value,
                MotivoInteraccion::AGENDAR_CITA_FINAL->value,
            ])
            ->chunkById(500, function ($interacciones) {
                $entrenamientos = DB::table('entrenamientos')
                    ->whereIn('paciente_id', $interacciones->pluck('paciente_id')->unique())
                    ->get()
                    ->groupBy('paciente_id');

                foreach ($interacciones as $interaccion) {
                    $fecha = substr($interaccion->fecha ?? '', 0, 10);
                    $delPaciente = $entrenamientos->get($interaccion->paciente_id, collect());
                    $anteriores = $delPaciente->filter(fn ($entrenamiento) => $entrenamiento->fecha <= $fecha);
                    $inicio = $anteriores->max('fecha');
                    $candidatos = $anteriores->filter(fn ($entrenamiento) => $interaccion->entrenamiento_id
                        ? $entrenamiento->id === $interaccion->entrenamiento_id
                        : $entrenamiento->fecha === $inicio);

                    if ($candidatos->count() !== 1) {
                        continue;
                    }

                    $entrenamiento = $candidatos->first();
                    $etapas = [];

                    foreach (Entrenamiento::VENTANAS as $etapa => $ventana) {
                        $motivo = Entrenamiento::motivoEtapa($etapa);
                        if ($motivo->value !== $interaccion->motivo
                            || $entrenamiento->{"{$ventana}_inicio"} <= $entrenamiento->fecha) {
                            continue;
                        }

                        if ($motivo === MotivoInteraccion::SEGUIMIENTO_MENSUAL
                            && ($etapa > $entrenamiento->seguimientos_mensuales
                                || $fecha < $entrenamiento->{"{$ventana}_inicio"}
                                || $fecha > $entrenamiento->{"{$ventana}_fin"})) {
                            continue;
                        }

                        $etapas[] = $etapa;
                    }

                    if (count($etapas) === 1) {
                        DB::table('interacciones')->where('id', $interaccion->id)->update([
                            'seguimiento_entrenamiento_id' => $entrenamiento->id,
                            'etapa_seguimiento' => $etapas[0],
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('interacciones', function (Blueprint $table) {
            $table->dropIndex('interacciones_etapa_seguimiento_index');
            $table->dropConstrainedForeignId('seguimiento_entrenamiento_id');
            $table->dropColumn('etapa_seguimiento');
        });
    }
};
