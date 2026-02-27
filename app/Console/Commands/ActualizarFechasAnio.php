<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ActualizarFechasAnio extends Command
{
    protected $signature = 'colma:actualizar-fechas-anio {year=2026} {--show-source}';

    protected $description = 'Actualiza el anio de campos de fecha manteniendo mes, dia y hora.';

    public function handle(): int
    {
        $year = (int) $this->argument('year');
        if ($this->option('show-source')) {
            $this->line('Command source: '.__FILE__);
        }

        if ($year < 1000 || $year > 9999) {
            $this->error('El anio debe estar entre 1000 y 9999.');
            return self::FAILURE;
        }

        $tables = [
            'glucosa' => ['fecha_registro', 'fecha_usuario', 'fecha_sincronizacion'],
            'presion_arterial' => ['fecha_registro', 'fecha_usuario', 'fecha_sincronizacion'],
            'estado_animo' => ['fecha_registro', 'fecha_usuario', 'fecha_sincronizacion'],
            'metas' => ['fecha_registro', 'fecha_usuario', 'fecha_sincronizacion'],
            'cuestionarios' => ['fecha_registro', 'fecha_usuario', 'fecha_sincronizacion'],
            'multimedia_read' => ['fecha_registro', 'fecha_sincronizacion'],
        ];

        $this->info("Actualizando anio a {$year}...");

        DB::beginTransaction();

        try {
            foreach ($tables as $table => $columns) {
                $zeroCheck = "CAST(%s AS CHAR(19)) = '0000-00-00 00:00:00'";
                $notZeroCheck = "CAST(%s AS CHAR(19)) <> '0000-00-00 00:00:00'";

                if (in_array('fecha_usuario', $columns, true)) {
                    $fixed = DB::table($table)
                        ->whereRaw(sprintf($zeroCheck, '`fecha_usuario`'))
                        ->update([
                            'fecha_usuario' => DB::raw('`fecha_registro`'),
                        ]);

                    $this->line("- {$table}.fecha_usuario: {$fixed} filas con cero copiadas desde fecha_registro");
                }

                foreach ($columns as $column) {
                    $base = "DATE_ADD(`{$column}`, INTERVAL ({$year} - YEAR(`{$column}`)) YEAR)";
                    $expression = "CASE WHEN {$base} > NOW() THEN DATE_ADD({$base}, INTERVAL -1 YEAR) ELSE {$base} END";

                    $updated = DB::table($table)
                        ->whereNotNull($column)
                        ->whereRaw(sprintf($notZeroCheck, "`{$column}`"))
                        ->update([
                            $column => DB::raw($expression),
                        ]);

                    $this->line("- {$table}.{$column}: {$updated} filas actualizadas");
                }
            }

            DB::commit();
            $this->info('Listo.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();

            $this->error('Error al actualizar fechas: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
