<?php

use App\Models\Entrenamiento;
use App\Models\Paciente;
use App\Models\TratamientoElemento;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progreso_elementos', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Paciente::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Entrenamiento::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(TratamientoElemento::class, 'tratamiento_elemento_id')
                ->constrained('tratamiento_elementos')
                ->cascadeOnDelete();
            $table->boolean('completado');
            $table->timestamp('fecha_estado');
            $table->timestamp('fecha_sincronizacion');
            $table->timestamps();

            $table->unique(['paciente_id', 'entrenamiento_id', 'tratamiento_elemento_id'], 'progreso_elemento_contexto_unico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progreso_elementos');
    }
};
