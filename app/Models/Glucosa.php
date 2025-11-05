<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Glucosa extends Model
{
    protected $table = 'glucosa';

    protected $fillable = [
        'paciente_id',
        'valor',
        'ayuno',
        'alerta',
        'fecha_registro',
        'fecha_sincronizacion',
    ];

    protected $casts = [
        'ayuno' => 'boolean',
        'alerta' => 'boolean',
        'fecha_registro' => 'datetime',
        'fecha_sincronizacion' => 'datetime',
    ];

    public $timestamps = false;

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }
}
