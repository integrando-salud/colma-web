<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Interaccion extends Model
{
    use SoftDeletes;

    public const MODALIDADES = [
        'Llamada por teléfono / WhatsApp',
        'Visita domiciliaria',
    ];

    public const MOTIVOS_REPROGRAMACION = [
        'No responde',
        'Teléfono apagado',
        'Número equivocado / cambió de número',
        'Pidió que se le llame más tarde',
    ];

    public const MOTIVOS_CONTACTO = [
        'Agendar cita de entrenamiento',
        'Cita de entrenamiento',
        'Seguimiento 2 semanas luego del tratamiento',
        'Seguimiento mensual',
        'Agendar cita final',
        'Cita final',
        'Re-entrenamiento',
        'Contacto iniciado por el participante',
    ];

    public const MOTIVOS_CONTACTO_ALERTA = [
        'No adherencia a las sesiones',
        'Problemas de conexión',
        'Alerta clínica',
        'Pedido de ayuda a través del aplicativo',
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
        'fecha',
        'hora',
        'modalidad',
        'motivo',
        'observaciones',
        'motivo_reprogramacion',
        'fecha_cita',
        'lugar_cita',
        'lugar_cita_otro',
        'fecha_cita_final',
        'hora_cita_final',
        'lugar_cita_final',
        'lugar_cita_final_otro',
        'seguimiento_pregunta_a',
        'seguimiento_pregunta_b',
        'seguimiento_pregunta_c',
        'seguimiento_pregunta_d',
        'seguimiento_pregunta_e',
        'seguimiento_pregunta_f',
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
        'pedido_ayuda_otro',
        'pedido_ayuda_pregunta_b',
        'pedido_ayuda_pregunta_c',
        'pedido_ayuda_pregunta_d',
    ];

    protected $casts = [
        'contactado' => 'boolean',
        'fecha' => 'datetime',
        'hora' => 'datetime',
        'fecha_cita' => 'datetime',
        'fecha_cita_final' => 'datetime',
        'hora_cita_final' => 'datetime',
        'seguimiento_pregunta_f' => 'datetime',
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
        return $this->belongsTo(User::class);
    }

    public function alerta()
    {
        return $this->belongsTo(Alerta::class);
    }
}
