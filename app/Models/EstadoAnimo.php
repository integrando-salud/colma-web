<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoAnimo extends Model
{
    protected $table = 'estado_animo';

    protected $fillable = [
        'id_app',
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
        'fecha_usuario',
        'fecha_sincronizacion',
    ];

    protected $casts = [
        'id_app' => 'integer',
        'pregunta_1' => 'integer',
        'pregunta_2' => 'integer',
        'pregunta_3' => 'integer',
        'pregunta_4' => 'integer',
        'pregunta_5' => 'integer',
        'pregunta_6' => 'integer',
        'pregunta_7' => 'integer',
        'pregunta_8' => 'integer',
        'pregunta_9' => 'integer',
        'valor' => 'integer',
        'fecha_registro' => 'datetime',
        'fecha_usuario' => 'datetime',
        'fecha_sincronizacion' => 'datetime',
    ];

    public $timestamps = false;

    public const string RANGO_NORMAL = '7 - 10';

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }
}
