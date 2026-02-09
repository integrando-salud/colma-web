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
        Schema::create('metas', function (Blueprint $table) {
            $table->id();
            $table->integer('id_app')->nullable();
            $table->foreignIdFor(Paciente::class)
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedInteger('semana');
            $table->unsignedInteger('sesion');
            $table->text('tipo_actividad');
            $table->text('actividad');
            $table->text('lugar');
            $table->text('acompaniante')->nullable();
            $table->boolean('meta_cumplida');
            $table->text('motivo_incumplimiento')->nullable();
            $table->timestamp('fecha_registro');
            $table->timestamp('fecha_usuario');
            $table->timestamp('fecha_sincronizacion');
            $table->timestamp('fecha_edicion')->nullable();
            $table->timestamp('fecha_objetivo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('metas', function (Blueprint $table) {
            $table->dropForeign(['paciente_id']);
        });

        Schema::dropIfExists('metas');
    }
};
