<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RespuestaQuiz extends Model
{
    protected $table = 'respuestas_quiz';

    protected $fillable = [
        'paciente_id',
        'entrenamiento_id',
        'tratamiento_elemento_id',
        'preguntas',
        'fecha_registro',
        'fecha_usuario',
        'fecha_sincronizacion',
    ];

    protected $casts = [
        'preguntas' => 'array',
        'fecha_registro' => 'datetime',
        'fecha_usuario' => 'datetime',
        'fecha_sincronizacion' => 'datetime',
    ];

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
