<?php

use App\Models\TratamientoElemento;
use App\Models\TratamientoSesion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tratamiento_versiones', function (Blueprint $table) {
            $table->json('contenido')->nullable()->after('hash');
        });

        Schema::table('tratamiento_sesiones', function (Blueprint $table) {
            $table->json('contenido')->nullable()->after('sesion');
        });

        Schema::create('tratamiento_elementos', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TratamientoSesion::class, 'tratamiento_sesion_id')
                ->constrained('tratamiento_sesiones')
                ->cascadeOnDelete();
            $table->string('tipo', 50);
            $table->unsignedInteger('orden');
            $table->json('contenido');
            $table->timestamps();

            $table->unique(['tratamiento_sesion_id', 'orden'], 'tratamiento_elemento_orden_unico');
        });

        Schema::table('tratamiento_multimedia', function (Blueprint $table) {
            $table->foreignIdFor(TratamientoElemento::class, 'tratamiento_elemento_id')
                ->nullable()
                ->after('tratamiento_sesion_id')
                ->constrained('tratamiento_elementos')
                ->cascadeOnDelete();
            $table->unique(['tratamiento_elemento_id', 'ruta'], 'tratamiento_multimedia_elemento_ruta_unica');
        });
    }

    public function down(): void
    {
        Schema::table('tratamiento_multimedia', function (Blueprint $table) {
            $table->dropUnique('tratamiento_multimedia_elemento_ruta_unica');
            $table->dropConstrainedForeignId('tratamiento_elemento_id');
        });
        Schema::dropIfExists('tratamiento_elementos');
        Schema::table('tratamiento_sesiones', fn (Blueprint $table) => $table->dropColumn('contenido'));
        Schema::table('tratamiento_versiones', fn (Blueprint $table) => $table->dropColumn('contenido'));
    }
};
