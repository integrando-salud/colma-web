<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The mobile application's identifiers are only unique within a patient.
     */
    public function up(): void
    {
        // Existing installations may have records created before sync was idempotent.
        // Keep the most recently inserted record (the greatest primary key) for every
        // key that is about to become unique. NULL values are deliberately not matched:
        // MySQL permits more than one NULL in a unique index and an absent app ID does
        // not identify the same record.
        $this->deleteDuplicates('glucosa', ['paciente_id', 'id_app']);
        $this->deleteDuplicates('presion_arterial', ['paciente_id', 'id_app']);
        $this->deleteDuplicates('estado_animo', ['paciente_id', 'id_app']);
        $this->deleteDuplicates('cuestionarios', ['paciente_id', 'id_app']);
        $this->deleteDuplicates('cuestionarios_preguntas', ['cuestionario_id', 'pregunta_index']);
        $this->deleteDuplicates('metas', ['paciente_id', 'id_app']);
        $this->deleteDuplicates('multimedia_read', ['paciente_id', 'id_app']);
        $this->deleteDuplicates('alertas', ['paciente_id', 'tipo', 'entidad_tipo', 'entidad_id']);

        Schema::table('glucosa', function (Blueprint $table) {
            $table->unique(['paciente_id', 'id_app']);
        });

        Schema::table('presion_arterial', function (Blueprint $table) {
            $table->unique(['paciente_id', 'id_app']);
        });

        Schema::table('estado_animo', function (Blueprint $table) {
            $table->unique(['paciente_id', 'id_app']);
        });

        Schema::table('cuestionarios', function (Blueprint $table) {
            $table->unique(['paciente_id', 'id_app']);
        });

        Schema::table('cuestionarios_preguntas', function (Blueprint $table) {
            $table->unique(['cuestionario_id', 'pregunta_index']);
        });

        Schema::table('metas', function (Blueprint $table) {
            $table->unique(['paciente_id', 'id_app']);
        });

        Schema::table('multimedia_read', function (Blueprint $table) {
            $table->unique(['paciente_id', 'id_app']);
        });

        Schema::table('alertas', function (Blueprint $table) {
            $table->unique(['paciente_id', 'tipo', 'entidad_tipo', 'entidad_id']);
        });
    }

    /**
     * Delete all but the last inserted row for each repeated composite key.
     */
    private function deleteDuplicates(string $table, array $columns): void
    {
        $matches = implode(' AND ', array_map(
            fn (string $column) => "newer.`{$column}` = older.`{$column}`",
            $columns,
        ));

        DB::statement(
            "DELETE older
             FROM `{$table}` AS older
             INNER JOIN `{$table}` AS newer
                ON {$matches}
               AND older.`id` < newer.`id`"
        );
    }

    public function down(): void
    {
        Schema::table('glucosa', function (Blueprint $table) {
            $table->dropUnique(['paciente_id', 'id_app']);
        });

        Schema::table('presion_arterial', function (Blueprint $table) {
            $table->dropUnique(['paciente_id', 'id_app']);
        });

        Schema::table('estado_animo', function (Blueprint $table) {
            $table->dropUnique(['paciente_id', 'id_app']);
        });

        Schema::table('cuestionarios', function (Blueprint $table) {
            $table->dropUnique(['paciente_id', 'id_app']);
        });

        Schema::table('cuestionarios_preguntas', function (Blueprint $table) {
            $table->dropUnique(['cuestionario_id', 'pregunta_index']);
        });

        Schema::table('metas', function (Blueprint $table) {
            $table->dropUnique(['paciente_id', 'id_app']);
        });

        Schema::table('multimedia_read', function (Blueprint $table) {
            $table->dropUnique(['paciente_id', 'id_app']);
        });

        Schema::table('alertas', function (Blueprint $table) {
            $table->dropUnique(['paciente_id', 'tipo', 'entidad_tipo', 'entidad_id']);
        });
    }
};
