<?php

use App\Models\Paciente;
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
        Schema::create('estado_animo', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Paciente::class)
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->integer('pregunta_1');
            $table->integer('pregunta_2');
            $table->integer('pregunta_3');
            $table->integer('pregunta_4');
            $table->integer('pregunta_5');
            $table->integer('pregunta_6');
            $table->integer('pregunta_7');
            $table->integer('pregunta_8');
            $table->integer('pregunta_9');
            $table->integer('valor');
            $table->timestamp('fecha_registro');
            $table->timestamp('fecha_sincronizacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estado_animo', function (Blueprint $table) {
            $table->dropForeign(['paciente_id']);
        });

        Schema::dropIfExists('estado_animo');
    }
};
