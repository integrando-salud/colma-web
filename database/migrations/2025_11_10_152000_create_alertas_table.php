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
        Schema::create('alertas', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Paciente::class)
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->enum('tipo', Alerta::TIPOS_ALERTA);
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->enum('entidad_tipo', Alerta::TIPOS_ENTIDADES)->nullable();
            $table->string('descripcion');
            $table->date('fecha');
            $table->date('fecha_contacto')->nullable();
            $table->string('recomendacion')->nullable();
            $table->dateTime('revisado')->nullable();
            $table->foreignIdFor(User::class)
                ->nullable()
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alertas', function (Blueprint $table) {
            $table->dropForeign(['paciente_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::dropIfExists('alertas');
    }
};
