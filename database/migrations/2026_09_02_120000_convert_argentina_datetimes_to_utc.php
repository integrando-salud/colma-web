<?php

use Illuminate\Database\Migrations\Migration;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columnas que representan instantes. Las columnas date y time de agenda
     * quedan fuera: representan una fecha u hora local, no un instante UTC.
     *
     * @var array<string, list<string>>
     */
    private const array INSTANT_COLUMNS = [
        'users' => ['ultimo_acceso', 'created_at', 'updated_at', 'deleted_at'],
        'pacientes' => ['fecha_ultima_sincronizacion', 'fecha_primer_login_app', 'created_at', 'updated_at', 'deleted_at'],
        'glucosa' => ['fecha_registro', 'fecha_usuario', 'fecha_sincronizacion'],
        'presion_arterial' => ['fecha_registro', 'fecha_usuario', 'fecha_sincronizacion'],
        'estado_animo' => ['fecha_registro', 'fecha_usuario', 'fecha_sincronizacion'],
        'cuestionarios' => ['fecha_registro', 'fecha_usuario', 'fecha_sincronizacion', 'created_at', 'updated_at'],
        'metas' => ['fecha_registro', 'fecha_usuario', 'fecha_sincronizacion', 'fecha_edicion', 'fecha_objetivo', 'created_at', 'updated_at'],
        'multimedia_read' => ['fecha_registro', 'fecha_sincronizacion', 'created_at', 'updated_at'],
        'alertas' => ['revisado', 'created_at', 'updated_at', 'deleted_at'],
        'interacciones' => ['created_at', 'updated_at', 'deleted_at'],
        'entrenamientos' => ['created_at', 'updated_at'],
        'pacientes_diagnosticos' => ['created_at', 'updated_at'],
        'cuestionarios_preguntas' => ['created_at', 'updated_at'],
        'personal_access_tokens' => ['last_used_at', 'expires_at', 'created_at', 'updated_at'],
        'abilities' => ['created_at', 'updated_at'],
        'roles' => ['created_at', 'updated_at'],
    ];

    /**
     * Los datos existentes fueron ingresados con America/Argentina/Buenos_Aires
     * como timezone de la aplicación. Esta migración conserva el mismo instante
     * al representarlo en UTC.
     */
    public function up(): void
    {
        foreach (self::INSTANT_COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $available = Schema::getColumnListing($table);
            $columns = array_values(array_intersect($columns, $available));

            if ($columns === [] || ! in_array('id', $available, true)) {
                continue;
            }

            DB::table($table)
                ->select(['id', ...$columns])
                ->orderBy('id')
                ->lazyById(500)
                ->each(function (object $row) use ($table, $columns): void {
                    $changes = [];

                    foreach ($columns as $column) {
                        if ($row->{$column} === null) {
                            continue;
                        }

                        $changes[$column] = CarbonImmutable::parse(
                            $row->{$column},
                            'America/Argentina/Buenos_Aires'
                        )->utc()->format('Y-m-d H:i:s');
                    }

                    if ($changes !== []) {
                        DB::table($table)->where('id', $row->id)->update($changes);
                    }
                });
        }
    }

    /**
     * No se revierte: luego de desplegar, pueden existir nuevas filas UTC y no
     * es posible distinguirlas de las migradas sin corromper sus instantes.
     */
    public function down(): void
    {
        // Migración de datos intencionalmente irreversible.
    }
};
