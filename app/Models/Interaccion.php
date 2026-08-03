<?php

namespace App\Models;

use App\Enums\MotivoInteraccion;
use App\Enums\MotivoReprogramacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Interaccion extends Model
{
    use SoftDeletes;

    public const array MODALIDADES = [
        'Llamada por teléfono / WhatsApp',
        'Encuentro presencial / en persona',
    ];

    public const array DESCRIPCION_CONTACTO = [
        0 => 'No contactado',
        1 => 'Contactado',
    ];

    protected $table = 'interacciones';

    protected $fillable = [
        'paciente_id',
        'user_id',
        'alerta_id',
        'contactado',
        'fecha',
        'hora',
        'modalidad',
        'motivo',
        'observaciones',
        'motivo_reprogramacion',
        'fecha_cita',
        'hora_cita',
        'lugar_cita',
        'lugar_cita_otro',
        'fecha_cita_final',
        'hora_cita_final',
        'seguimiento_pregunta_a',
        'seguimiento_pregunta_b',
        'seguimiento_pregunta_c',
        'seguimiento_pregunta_d',
        'seguimiento_pregunta_e',
        'fecha_reentrenamiento',
        'hora_reentrenamiento',
        'lugar_reentrenamiento',
        'lugar_reentrenamiento_otro',
        'razon_contacto_participante',
        'razon_contacto_participante_otro',
        'contacto_participante_acciones',
        'nueva_fecha_contacto',
        'no_adherencia_pregunta_a',
        'no_adherencia_otro',
        'no_adherencia_pregunta_b',
        'no_adherencia_pregunta_c',
        'problemas_conexion_pregunta_a',
        'problemas_conexion_otro',
        'problemas_conexion_pregunta_b',
        'problemas_conexion_pregunta_c',
        'alerta_clinica_pregunta_a',
        'alerta_clinica_pregunta_b',
        'alerta_clinica_pregunta_c',
        'alerta_clinica_pregunta_d',
        'alerta_clinica_pregunta_e',
        'alerta_clinica_pregunta_f',
        'pedido_ayuda_pregunta_a',
        'pedido_ayuda_pregunta_b',
        'pedido_ayuda_pregunta_c',
        'pedido_ayuda_pregunta_d',
    ];

    protected $casts = [
        'contactado' => 'boolean',
        'motivo' => MotivoInteraccion::class,
        'motivo_reprogramacion' => MotivoReprogramacion::class,
        'fecha' => 'datetime',
        'hora' => 'datetime',
        'fecha_cita' => 'datetime',
        'hora_cita' => 'datetime',
        'fecha_cita_final' => 'datetime',
        'hora_cita_final' => 'datetime',
        'fecha_reentrenamiento' => 'datetime',
        'hora_reentrenamiento' => 'datetime',
        'nueva_fecha_contacto' => 'datetime',
        'no_adherencia_pregunta_a' => 'array',
        'no_adherencia_pregunta_c' => 'datetime',
        'problemas_conexion_pregunta_a' => 'array',
        'problemas_conexion_pregunta_c' => 'datetime',
        'pedido_ayuda_pregunta_a' => 'array',
        'pedido_ayuda_pregunta_d' => 'datetime',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function alerta()
    {
        return $this->belongsTo(Alerta::class);
    }
}
