<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresionArterial extends Model
{
    protected $table = 'presion_arterial';

    protected $fillable = [
        'paciente_id',
        'presion_sistolica',
        'presion_diastolica',
        'alerta',
        'fecha_registro',
        'fecha_sincronizacion',
    ];

    protected $casts = [
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
