<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entrenamiento extends Model
{
    protected $fillable = [
        'paciente_id',
        'fecha',
        'sesiones',
        'sesiones_completadas',
        'seguimiento_dos_semanas_inicio',
        'seguimiento_dos_semanas_fin',
        'llamada_mensual_1_inicio',
        'llamada_mensual_1_fin',
        'llamada_mensual_2_inicio',
        'llamada_mensual_2_fin',
        'llamada_mensual_3_inicio',
        'llamada_mensual_3_fin',
        'llamada_mensual_4_inicio',
        'llamada_mensual_4_fin',
        'llamada_mensual_5_inicio',
        'llamada_mensual_5_fin',
        'agendar_cita_final_inicio',
        'agendar_cita_final_fin',
        'activo',
        'user_id',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'sesiones' => 'integer',
        'sesiones_completadas' => 'integer',
        'fecha' => 'date',
        'seguimiento_dos_semanas_inicio' => 'date',
        'seguimiento_dos_semanas_fin' => 'date',
        'llamada_mensual_1_inicio' => 'date',
        'llamada_mensual_1_fin' => 'date',
        'llamada_mensual_2_inicio' => 'date',
        'llamada_mensual_2_fin' => 'date',
        'llamada_mensual_3_inicio' => 'date',
        'llamada_mensual_3_fin' => 'date',
        'llamada_mensual_4_inicio' => 'date',
        'llamada_mensual_4_fin' => 'date',
        'llamada_mensual_5_inicio' => 'date',
        'llamada_mensual_5_fin' => 'date',
        'agendar_cita_final_inicio' => 'date',
        'agendar_cita_final_fin' => 'date',
    ];

    const array DIAS_SUMA = [
        'seguimiento_dos_semanas_inicio' => 14,
        'seguimiento_dos_semanas_fin' => 21,
        'llamada_mensual_1_inicio' => 28,
        'llamada_mensual_1_fin' => 42,
        'llamada_mensual_2_inicio' => 56,
        'llamada_mensual_2_fin' => 70,
        'llamada_mensual_3_inicio' => 84,
        'llamada_mensual_3_fin' => 98,
        'llamada_mensual_4_inicio' => 112,
        'llamada_mensual_4_fin' => 126,
        'llamada_mensual_5_inicio' => 140,
        'llamada_mensual_5_fin' => 154,
        'agendar_cita_final_inicio' => 168,
        'agendar_cita_final_fin' => 182,
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
