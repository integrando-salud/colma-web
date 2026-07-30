<?php

namespace App\Models;

use App\Enums\AdvertenciaClinica;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PresionArterial extends Model
{
    use HasFactory;

    protected $table = 'presion_arterial';

    protected $fillable = [
        'id_app',
        'paciente_id',
        'presion_sistolica',
        'presion_diastolica',
        'fecha_registro',
        'fecha_usuario',
        'fecha_sincronizacion',
        'advertencia',
    ];

    protected $casts = [
        'id_app' => 'integer',
        'presion_sistolica' => 'integer',
        'presion_diastolica' => 'integer',
        'advertencia' => AdvertenciaClinica::class,
        'fecha_registro' => 'datetime',
        'fecha_usuario' => 'datetime',
        'fecha_sincronizacion' => 'datetime',
    ];

    public $timestamps = false;

    public const RANGO_NORMAL = '120 - 80';

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function getValorAttribute()
    {
        return "$this->presion_sistolica - $this->presion_diastolica";
    }
}
