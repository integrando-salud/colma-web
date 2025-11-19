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
        Schema::create('presion_arterial', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Paciente::class)
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->integer('presion_sistolica');
            $table->integer('presion_diastolica');
            $table->timestamp('fecha_registro');
            $table->timestamp('fecha_sincronizacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presion_arterial', function (Blueprint $table) {
            $table->dropForeign(['paciente_id']);
        });

        Schema::dropIfExists('presion_arterial');
    }
};
