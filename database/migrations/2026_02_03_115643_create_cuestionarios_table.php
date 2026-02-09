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
        Schema::create('cuestionarios', function (Blueprint $table) {
            $table->id();
            $table->integer('id_app')->nullable();
            $table->enum('quiz_type', ['hta', 'diabetes']);
            $table->foreignIdFor(Paciente::class)
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->timestamp('fecha_registro');
            $table->timestamp('fecha_usuario');
            $table->timestamp('fecha_sincronizacion');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuestionarios', function (Blueprint $table) {
            $table->dropForeign(['paciente_id']);
        });

        Schema::dropIfExists('cuestionarios');
    }
};
