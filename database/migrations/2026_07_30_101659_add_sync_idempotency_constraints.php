<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The mobile application's identifiers are only unique within a patient.
     */
    public function up(): void
    {
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
