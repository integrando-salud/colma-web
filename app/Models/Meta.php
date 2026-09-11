<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meta extends Model
{
    protected $fillable = [
        'id_app',
        'paciente_id',
        'entrenamiento_id',
        'semana',
        'sesion',
        'tipo_actividad',
        'actividad',
        'lugar',
        'acompaniante',
        'meta_cumplida',
        'motivo_incumplimiento',
        'fecha_registro',
        'fecha_usuario',
        'fecha_sincronizacion',
        'fecha_edicion',
        'fecha_objetivo',
    ];

    protected $casts = [
        'id_app' => 'integer',
        'meta_cumplida' => 'boolean',
        'fecha_registro' => 'datetime',
        'fecha_usuario' => 'datetime',
        'fecha_sincronizacion' => 'datetime',
        'fecha_edicion' => 'datetime',
        'fecha_objetivo' => 'datetime',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function entrenamiento()
    {
        return $this->belongsTo(Entrenamiento::class);
    }
}
