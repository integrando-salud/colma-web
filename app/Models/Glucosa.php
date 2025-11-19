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
        'fecha_registro',
        'fecha_sincronizacion',
    ];

    protected $casts = [
        'ayuno' => 'boolean',
        'fecha_registro' => 'datetime',
        'fecha_sincronizacion' => 'datetime',
    ];

    public $timestamps = false;

    public const RANGO_NORMAL = '70 - 99';

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }
}
