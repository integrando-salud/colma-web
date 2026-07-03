<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Alerta extends Model
{
    use SoftDeletes;

    public const TIPOS_ALERTA = [
        'clinica',
        'operativa',
        'calls' //TODO: change name to Spanish?
    ];

    public const TIPOS_ENTIDADES = [
        'glucosa',
        'presion_arterial',
        'estado_animo',
        'appointment'
    ];

    protected $fillable = [
        'paciente_id',
        'tipo',
        'entidad_id',
        'entidad_tipo',
        'descripcion',
        'fecha',
        'fecha_contacto',
        'recomendacion',
        'revisado',
        'user_id',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'fecha_contacto' => 'datetime',
        'revisado' => 'datetime',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function interacciones()
    {
        return $this->hasMany(Interaccion::class);
    }
}
