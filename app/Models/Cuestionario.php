<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cuestionario extends Model
{
    protected $fillable = [
        'id_app',
        'paciente_id',
        'quiz_type',
        'fecha_registro',
        'fecha_usuario',
        'fecha_sincronizacion',
    ];

    protected $casts = [
        'fecha_registro' => 'datetime',
        'fecha_usuario' => 'datetime',
        'fecha_sincronizacion' => 'datetime',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function preguntas()
    {
        return $this->hasMany(CuestionarioPregunta::class);
    }
}
