<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Los datos de prueba existentes sólo se vinculaban por semana/sesión.
        // No existe una asociación inequívoca sin heurísticas, por lo que se
        // eliminan antes de imponer la identidad estructural definitiva.
        DB::table('metas')->delete();

        $columnas = Schema::getColumnListing('metas');
        $indices = collect(Schema::getIndexes('metas'))->pluck('name');

        Schema::table('metas', function (Blueprint $table) use ($columnas, $indices) {
            // La FK de paciente usaba el índice único legacy como soporte.
            if (! $indices->contains('metas_paciente_id_index')) {
                $table->index('paciente_id');
            }
            if ($indices->contains('metas_paciente_id_id_app_unique')) {
                $table->dropUnique(['paciente_id', 'id_app']);
            }
            $columnasLegacy = array_values(array_intersect(['semana', 'sesion'], $columnas));
            if ($columnasLegacy !== []) {
                $table->dropColumn($columnasLegacy);
            }
            if (! in_array('tratamiento_elemento_id', $columnas, true)) {
                $table->foreignId('tratamiento_elemento_id')
                    ->after('entrenamiento_id')
                    ->constrained('tratamiento_elementos')
                    ->cascadeOnDelete();
            }
            $table->dropForeign(['entrenamiento_id']);
            $table->unsignedInteger('id_app')->nullable(false)->change();
            $table->foreignId('entrenamiento_id')->nullable(false)->change();
            $table->foreign('entrenamiento_id')->references('id')->on('entrenamientos')->cascadeOnDelete();
            $table->unique(
                ['paciente_id', 'entrenamiento_id', 'tratamiento_elemento_id', 'id_app'],
                'meta_elemento_contexto_unico',
            );
        });

        Schema::create('respuestas_quiz', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained()->cascadeOnDelete();
            $table->foreignId('entrenamiento_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tratamiento_elemento_id')->constrained()->cascadeOnDelete();
            $table->json('preguntas');
            $table->timestamp('fecha_registro');
            $table->timestamp('fecha_usuario');
            $table->timestamp('fecha_sincronizacion');
            $table->timestamps();

            $table->unique(
                ['paciente_id', 'entrenamiento_id', 'tratamiento_elemento_id'],
                'respuesta_quiz_contexto_unico',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('respuestas_quiz');
        // La eliminación de datos de prueba y columnas estructurales es
        // intencionalmente irreversible.
    }
};
