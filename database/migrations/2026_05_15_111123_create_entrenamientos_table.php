<?php

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
        Schema::create('entrenamientos', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Paciente::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->date('fecha');
            $table->date('seguimiento_dos_semanas_inicio');
            $table->date('seguimiento_dos_semanas_fin');
            $table->date('llamada_mensual_1_inicio');
            $table->date('llamada_mensual_1_fin');
            $table->date('llamada_mensual_2_inicio');
            $table->date('llamada_mensual_2_fin');
            $table->date('llamada_mensual_3_inicio');
            $table->date('llamada_mensual_3_fin');
            $table->date('llamada_mensual_4_inicio');
            $table->date('llamada_mensual_4_fin');
            $table->date('llamada_mensual_5_inicio');
            $table->date('llamada_mensual_5_fin');
            $table->date('agendar_cita_final_inicio');
            $table->date('agendar_cita_final_fin');
            $table->boolean('activo')->default(true);
            $table->foreignIdFor(User::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entrenamientos');
    }
};
