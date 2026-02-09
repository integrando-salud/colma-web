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
        Schema::create('multimedia_read', function (Blueprint $table) {
            $table->id();
            $table->integer('id_app')->nullable();
            $table->foreignIdFor(Paciente::class)
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('tratamiento', 50);
            $table->unsignedInteger('semana');
            $table->unsignedInteger('sesion');
            $table->string('tipo', 50);
            $table->string('key');
            $table->timestamp('fecha_registro');
            $table->timestamp('fecha_sincronizacion');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('multimedia_read', function (Blueprint $table) {
            $table->dropForeign(['paciente_id']);
        });

        Schema::dropIfExists('multimedia_read');
    }
};
