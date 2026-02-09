<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MultimediaRead extends Model
{
    protected $table = 'multimedia_read';

    protected $fillable = [
        'id_app',
        'paciente_id',
        'tratamiento',
        'semana',
        'sesion',
        'tipo',
        'key',
        'fecha_registro',
        'fecha_sincronizacion',
    ];

    protected $casts = [
        'fecha_registro' => 'datetime',
        'fecha_sincronizacion' => 'datetime',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }
}
