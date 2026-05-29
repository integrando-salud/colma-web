<?php

namespace App\Console\Commands;

use App\Models\Alerta;
use App\Models\EstadoAnimo;
use App\Models\Glucosa;
use App\Models\PresionArterial;
use App\Services\DatosClinicosService;
use Illuminate\Console\Command;

class RecalcularAlertasClinicas extends Command
{
    protected $signature = 'colma:recalcular-alertas-clinicas {--chunk=500} {--dry-run}';

    protected $description = 'Recalcula recomendaciones clínicas para glucosa, presión arterial y estado de ánimo.';

    public function handle(DatosClinicosService $service): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');

        $totals = [
            'glucosa' => 0,
            'presion_arterial' => 0,
            'estado_animo' => 0,
            'creadas' => 0,
            'actualizadas' => 0,
        ];

        $this->info('Recalculando recomendaciones...');

        Glucosa::query()
            ->orderBy('id')
            ->chunkById($chunkSize, function ($registros) use ($service, $dryRun, &$totals) {
                foreach ($registros as $registro) {
                    $recomendacion = $service->getRecomendacionGlucosa((int) $registro->valor_mgdl);
                    if ($recomendacion === null) {
                        continue;
                    }

                    $descripcion = $registro->valor_mgdl < 70
                        ? 'Glucosa baja'
                        : 'Glucosa elevada / alta';

                    $recomendacion .= PHP_EOL . 'Completar formulario de alerta clínica.';

                    $this->upsertAlerta(
                        $registro->paciente_id,
                        'glucosa',
                        $registro->id,
                        $descripcion,
                        $recomendacion,
                        $dryRun,
                        $totals
                    );

                    $totals['glucosa']++;
                }
            });

        PresionArterial::query()
            ->orderBy('id')
            ->chunkById($chunkSize, function ($registros) use ($service, $dryRun, &$totals) {
                foreach ($registros as $registro) {
                    $recomendacion = $service->getRecomendacionPresionArterial(
                        (int) $registro->presion_sistolica,
                        (int) $registro->presion_diastolica,
                        (int) $registro->paciente_id
                    );
                    if ($recomendacion === null) {
                        continue;
                    }

                    $descripcion = $registro->presion_sistolica < 90
                        ? 'Presión arterial baja'
                        : 'Presión arterial elevada / alta';

                    $recomendacion .= PHP_EOL . 'Completar formulario de alerta clínica.';

                    $this->upsertAlerta(
                        $registro->paciente_id,
                        'presion_arterial',
                        $registro->id,
                        $descripcion,
                        $recomendacion,
                        $dryRun,
                        $totals
                    );

                    $totals['presion_arterial']++;
                }
            });

        EstadoAnimo::query()
            ->orderBy('id')
            ->chunkById($chunkSize, function ($registros) use ($service, $dryRun, &$totals) {
                foreach ($registros as $registro) {
                    $recomendacion = $service->getRecomendacionEstadoAnimo((int) $registro->valor);
                    if ($recomendacion === null) {
                        continue;
                    }

                    $recomendacion .= PHP_EOL . 'Completar formulario de alerta clínica.';

                    $this->upsertAlerta(
                        $registro->paciente_id,
                        'estado_animo',
                        $registro->id,
                        'Estado de ánimo alterado',
                        $recomendacion,
                        $dryRun,
                        $totals
                    );

                    $totals['estado_animo']++;
                }
            });

        $operativas = $this->actualizarAlertasOperativas($dryRun);
        $totals['operativas_actualizadas'] = $operativas;

        $this->line('');
        $this->info('Resumen:');
        $this->line("- Registros glucosa procesados: {$totals['glucosa']}");
        $this->line("- Registros presión arterial procesados: {$totals['presion_arterial']}");
        $this->line("- Registros estado de ánimo procesados: {$totals['estado_animo']}");
        $this->line("- Alertas creadas: {$totals['creadas']}");
        $this->line("- Alertas actualizadas: {$totals['actualizadas']}");
        $this->line("- Alertas operativas actualizadas: {$totals['operativas_actualizadas']}");

        if ($dryRun) {
            $this->line('');
            $this->comment('Dry run: no se escribieron cambios.');
        }

        return self::SUCCESS;
    }

    private function actualizarAlertasOperativas(bool $dryRun): int
    {
        $recomendaciones = [
            'No se conecta' => "Contactar y preguntar si es que tiene problemas con el app o el celular o el internet. Intentar dar solución y/o motivarla a seguir usando el app.\nCompletar formulario de alerta operativa.",
            'Problemas de conexión' => "Contactar y preguntar si es que tiene problemas con el app o el celular o el internet. Intentar dar solución y/o motivarla a seguir usando el app.\nCompletar formulario de alerta operativa.",
            'Pedido de ayuda' => "Contactar y explorar motivo del pedido de ayuda. Intentar dar solución.\nCompletar formulario de alerta operativa.",
        ];

        $query = Alerta::query()
            ->where('tipo', 'operativa')
            ->whereIn('descripcion', array_keys($recomendaciones));

        if ($dryRun) {
            return (int) $query->count();
        }

        return (int) $query->get()->each(function (Alerta $alerta) use ($recomendaciones) {
            $fechaBase = $alerta->fecha ?? now();
            $alerta->update([
                'recomendacion' => $recomendaciones[$alerta->descripcion],
                'fecha_contacto' => $fechaBase->copy()->addDays(3)->format('Y-m-d'),
            ]);
        })->count();
    }

    private function upsertAlerta(
        int $pacienteId,
        string $entidadTipo,
        int $entidadId,
        string $descripcion,
        string $recomendacion,
        bool $dryRun,
        array &$totals
    ): void {
        $alerta = Alerta::query()
            ->where('paciente_id', $pacienteId)
            ->where('tipo', 'clinica')
            ->where('entidad_tipo', $entidadTipo)
            ->where('entidad_id', $entidadId)
            ->first();

        if ($alerta) {
            if (!$dryRun) {
                $fechaBase = $alerta->fecha ?? now();
                $fechaContacto = $fechaBase->copy()->addDays(2)->format('Y-m-d');

                $alerta->update([
                    'recomendacion' => $recomendacion,
                    'fecha_contacto' => $fechaContacto,
                ]);
            }

            $totals['actualizadas']++;
            return;
        }

        if (!$dryRun) {
            $fecha = now();
            Alerta::create([
                'paciente_id' => $pacienteId,
                'tipo' => 'clinica',
                'entidad_id' => $entidadId,
                'entidad_tipo' => $entidadTipo,
                'descripcion' => $descripcion,
                'recomendacion' => $recomendacion,
                'fecha' => $fecha,
                'fecha_contacto' => $fecha->copy()->addDays(2)->format('Y-m-d'),
            ]);
        }

        $totals['creadas']++;
    }
}
