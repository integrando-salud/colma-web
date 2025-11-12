<?php

use App\Models\Interaccion;
use App\Models\Paciente;
use App\Models\User;
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
        Schema::create('interacciones', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Paciente::class)
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignIdFor(User::class)
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedInteger('alerta_id')->nullable();
            $table->enum('alerta_tipo', Interaccion::TIPOS_ALERTA)->nullable();
            $table->boolean('contactado');
            $table->enum('razon_no_contacto', Interaccion::RAZONES_NO_CONTACTO)->nullable();
            $table->date('fecha');
            $table->time('hora');
            $table->enum('modalidad', Interaccion::MODALIDADES);
            $table->enum('motivo', Interaccion::MOTIVOS_CONTACTO);
            $table->text('observaciones')->nullable();
            $table->enum('accion', Interaccion::ACCIONES)->nullable();
            $table->date('fecha_reprogramacion')->nullable();
            $table->enum('motivo_reprogramacion', Interaccion::MOTIVOS_REPROGRAMACION)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interacciones', function (Blueprint $table) {
            $table->dropForeign(['paciente_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::dropIfExists('interacciones');
    }
};
