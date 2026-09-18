<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgresoElemento extends Model
{
    protected $table = 'progreso_elementos';

    protected $fillable = [
        'paciente_id',
        'entrenamiento_id',
        'tratamiento_elemento_id',
        'completado',
        'fecha_estado',
        'fecha_sincronizacion',
    ];

    protected function casts(): array
    {
        return [
            'completado' => 'boolean',
            'fecha_estado' => 'datetime',
            'fecha_sincronizacion' => 'datetime',
        ];
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function entrenamiento()
    {
        return $this->belongsTo(Entrenamiento::class);
    }

    public function tratamientoElemento()
    {
        return $this->belongsTo(TratamientoElemento::class);
    }
}
