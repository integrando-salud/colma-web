<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cuestionarios_preguntas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuestionario_id')
                ->constrained('cuestionarios')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedInteger('pregunta_index');
            $table->text('pregunta_texto');
            $table->unsignedInteger('respuesta_usuario');
            $table->text('respuesta_usuario_texto');
            $table->unsignedInteger('respuesta_correcta');
            $table->text('respuesta_correcta_texto');
            $table->boolean('es_correcta');
            $table->json('respuestas_posibles');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuestionarios_preguntas', function (Blueprint $table) {
            $table->dropForeign(['cuestionario_id']);
        });

        Schema::dropIfExists('cuestionarios_preguntas');
    }
};
