<?php

use App\Models\TratamientoMultimedia;
use App\Models\TratamientoSesion;
use App\Models\TratamientoVersion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tratamiento_versiones', function (Blueprint $table) {
            $table->id();
            $table->string('pais', 10);
            $table->string('tipo', 50);
            $table->string('hash', 64);
            $table->timestamps();

            $table->unique(['pais', 'tipo', 'hash']);
        });

        Schema::create('tratamiento_sesiones', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TratamientoVersion::class, 'tratamiento_version_id')
                ->constrained('tratamiento_versiones')
                ->cascadeOnDelete();
            $table->unsignedInteger('semana');
            $table->unsignedInteger('sesion');
            $table->timestamps();

            $table->unique(['tratamiento_version_id', 'semana', 'sesion'], 'tratamiento_sesion_unica');
        });

        Schema::create('tratamiento_multimedia', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TratamientoSesion::class, 'tratamiento_sesion_id')
                ->constrained('tratamiento_sesiones')
                ->cascadeOnDelete();
            $table->string('ruta');
            $table->string('tipo', 50);
            $table->string('checksum', 64);
            $table->timestamps();

            $table->unique(['tratamiento_sesion_id', 'ruta'], 'tratamiento_multimedia_ruta_unica');
        });

        Schema::table('entrenamientos', function (Blueprint $table) {
            $table->foreignIdFor(TratamientoVersion::class, 'tratamiento_version_id')
                ->nullable()
                ->after('paciente_id')
                ->constrained('tratamiento_versiones')
                ->nullOnDelete();
        });

        Schema::table('multimedia_read', function (Blueprint $table) {
            $table->foreignIdFor(TratamientoMultimedia::class, 'tratamiento_multimedia_id')
                ->nullable()
                ->after('entrenamiento_id')
                ->constrained('tratamiento_multimedia')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('multimedia_read', fn (Blueprint $table) => $table->dropConstrainedForeignId('tratamiento_multimedia_id'));
        Schema::table('entrenamientos', fn (Blueprint $table) => $table->dropConstrainedForeignId('tratamiento_version_id'));
        Schema::dropIfExists('tratamiento_multimedia');
        Schema::dropIfExists('tratamiento_sesiones');
        Schema::dropIfExists('tratamiento_versiones');
    }
};
