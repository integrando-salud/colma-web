<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PresionArterial extends Model
{
    use HasFactory;

    protected $table = 'presion_arterial';

    protected $fillable = [
        'paciente_id',
        'presion_sistolica',
        'presion_diastolica',
        'fecha_registro',
        'fecha_sincronizacion',
    ];

    protected $casts = [
        'fecha_registro' => 'datetime',
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
