<?php

use App\Models\Alerta;
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
            $table->foreignIdFor(Alerta::class)
                ->nullable()
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->boolean('contactado');
            $table->date('fecha');
            $table->time('hora');
            $table->string('modalidad');
            $table->string('motivo');
            $table->text('observaciones')->nullable();
            $table->string('motivo_reprogramacion')->nullable();
            $table->date('fecha_cita')->nullable();
            $table->string('lugar_cita')->nullable();
            $table->string('lugar_cita_otro')->nullable();
            $table->date('fecha_cita_final')->nullable();
            $table->time('hora_cita_final')->nullable();
            $table->string('lugar_cita_final')->nullable();
            $table->string('lugar_cita_final_otro')->nullable();
            $table->enum('seguimiento_pregunta_a', ['Si', 'No'])->nullable();
            $table->text('seguimiento_pregunta_b')->nullable();
            $table->enum('seguimiento_pregunta_c', ['Si', 'No'])->nullable();
            $table->text('seguimiento_pregunta_d')->nullable();
            $table->enum('seguimiento_pregunta_e', ['Si', 'No'])->nullable();
            $table->date('seguimiento_pregunta_f')->nullable();
            $table->string('razon_contacto_participante')->nullable();
            $table->string('razon_contacto_participante_otro')->nullable();
            $table->text('contacto_participante_acciones')->nullable();
            $table->date('nueva_fecha_contacto')->nullable();
            $table->json('no_adherencia_pregunta_a')->nullable();
            $table->string('no_adherencia_otro')->nullable();
            $table->enum('no_adherencia_pregunta_b', ['Si', 'No'])->nullable();
            $table->date('no_adherencia_pregunta_c')->nullable();
            $table->json('problemas_conexion_pregunta_a')->nullable();
            $table->string('problemas_conexion_otro')->nullable();
            $table->enum('problemas_conexion_pregunta_b', ['Si', 'No'])->nullable();
            $table->date('problemas_conexion_pregunta_c')->nullable();
            $table->enum('alerta_clinica_pregunta_a', ['Si', 'No'])->nullable();
            $table->enum('alerta_clinica_pregunta_b', ['Si', 'No'])->nullable();
            $table->enum('alerta_clinica_pregunta_c', ['Si', 'No'])->nullable();
            $table->text('alerta_clinica_pregunta_d')->nullable();
            $table->enum('alerta_clinica_pregunta_e', ['Si', 'No'])->nullable();
            $table->text('alerta_clinica_pregunta_f')->nullable();
            $table->json('pedido_ayuda_pregunta_a')->nullable();
            $table->string('pedido_ayuda_otro')->nullable();
            $table->text('pedido_ayuda_pregunta_b')->nullable();
            $table->enum('pedido_ayuda_pregunta_c', ['Si', 'No'])->nullable();
            $table->date('pedido_ayuda_pregunta_d')->nullable();
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
            $table->dropForeign(['alerta_id']);
        });

        Schema::dropIfExists('interacciones');
    }
};
