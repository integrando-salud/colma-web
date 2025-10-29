<?php

use App\Models\CentroSalud;
use App\Models\Paquete;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'user_id')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->date('fecha_nacimiento');
            $table->enum('sexo', ['M', 'F']);
            $table->string('documento', 20)->unique();
            $table->string('telefono', 30);
            $table->string('email', 100);
            $table->string('direccion');
            $table->foreignIdFor(CentroSalud::class, 'centro_salud_id')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignIdFor(User::class, 'promotor_id')
                ->nullable()
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('set null');
            $table->date('fecha_enrolamiento');
            $table->foreignIdFor(Paquete::class, 'paquete_id')
                ->nullable()
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('set null');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['centro_salud_id']);
            $table->dropForeign(['promotor_id']);
            $table->dropForeign(['paquete_id']);
        });

        Schema::dropIfExists('pacientes');
    }
};
