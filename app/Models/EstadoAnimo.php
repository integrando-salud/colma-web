<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoAnimo extends Model
{
    protected $table = 'estado_animo';

    protected $fillable = [
        'paciente_id',
        'pregunta_1',
        'pregunta_2',
        'pregunta_3',
        'pregunta_4',
        'pregunta_5',
        'pregunta_6',
        'pregunta_7',
        'pregunta_8',
        'pregunta_9',
        'valor',
        'fecha_registro',
        'fecha_sincronizacion',
    ];

    protected $casts = [
        'fecha_registro' => 'datetime',
        'fecha_sincronizacion' => 'datetime',
    ];

    public $timestamps = false;

    public const RANGO_NORMAL = '7 - 10';

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }
}
