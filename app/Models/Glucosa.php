<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Glucosa extends Model
{
    use HasFactory;

    protected $table = 'glucosa';

    protected $fillable = [
        'id_app',
        'paciente_id',
        'valor_mgdl',
        'ayuno',
        'fecha_registro',
        'fecha_usuario',
        'fecha_sincronizacion',
        'advertencia',
    ];

    protected $casts = [
        'ayuno' => 'boolean',
        'fecha_registro' => 'datetime',
        'fecha_usuario' => 'datetime',
        'fecha_sincronizacion' => 'datetime',
    ];

    public $timestamps = false;

    public const RANGO_NORMAL = '70 - 99';

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }
}
