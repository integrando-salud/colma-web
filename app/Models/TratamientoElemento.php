<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TratamientoElemento extends Model
{
    protected $table = 'tratamiento_elementos';

    protected $fillable = ['tratamiento_sesion_id', 'tipo', 'orden', 'contenido'];

    protected function casts(): array
    {
        return ['contenido' => 'array'];
    }

    public function sesion()
    {
        return $this->belongsTo(TratamientoSesion::class, 'tratamiento_sesion_id');
    }

    public function multimedia()
    {
        return $this->hasMany(TratamientoMultimedia::class);
    }

    public function progresos()
    {
        return $this->hasMany(ProgresoElemento::class);
    }

    public function metas()
    {
        return $this->hasMany(Meta::class);
    }

    public function respuestasQuiz()
    {
        return $this->hasMany(RespuestaQuiz::class);
    }
}
