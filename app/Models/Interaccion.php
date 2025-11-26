<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Interaccion extends Model
{
    use SoftDeletes;

    public const RAZONES_NO_CONTACTO = [
        'No contesta',
        'Teléfono inválido'
    ];

    public const MODALIDADES = [
        'Teléfono',
        'Visita',
        'Mail',
        'WhatsApp'
    ];

    public const ACCIONES = [
        'Derivado',
        'Consejo',
        'Reagendado'
    ];

    public const MOTIVOS_REPROGRAMACION = [
        'Motivo 1',
        'Motivo 2',
        'Motivo 3',
    ];

    public const MOTIVOS_CONTACTO = [
        'Entrenamiento inicial',
        'Falta de conexión',
        'Falta de adherencia',
        'Alerta clínica',
        'Pedido ayuda',
        'Seguimiento programado',
        'Reprogramación',
    ];

    public const DESCRIPCION_CONTACTO = [
        0 => 'No contactado',
        1 => 'Contactado',
    ];

    protected $table = 'interacciones';

    protected $fillable = [
        'paciente_id',
        'user_id',
        'alerta_id',
        'contactado',
        'razon_no_contacto',
        'fecha',
        'hora',
        'modalidad',
        'motivo',
        'observaciones',
        'accion',
        'fecha_reprogramacion',
        'motivo_reprogramacion',
    ];

    protected $casts = [
        'contactado' => 'boolean',
        'fecha' => 'datetime',
        'hora' => 'datetime',
        'fecha_reprogramacion' => 'datetime',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function alerta()
    {
        return $this->belongsTo(Alerta::class);
    }
}
