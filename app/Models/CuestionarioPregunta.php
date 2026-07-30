<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CuestionarioPregunta extends Model
{
    protected $table = 'cuestionarios_preguntas';

    protected $fillable = [
        'cuestionario_id',
        'pregunta_index',
        'pregunta_texto',
        'respuesta_usuario',
        'respuesta_usuario_texto',
        'respuesta_correcta',
        'respuesta_correcta_texto',
        'es_correcta',
        'respuestas_posibles',
    ];

    protected $casts = [
        'pregunta_index' => 'integer',
        'respuesta_usuario' => 'integer',
        'respuesta_correcta' => 'integer',
        'es_correcta' => 'boolean',
        'respuestas_posibles' => 'array',
    ];

    public function cuestionario()
    {
        return $this->belongsTo(Cuestionario::class);
    }
}
