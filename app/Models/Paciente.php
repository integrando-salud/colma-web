<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Paciente extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'nombre',
        'apellido',
        'fecha_nacimiento',
        'sexo',
        'documento',
        'telefono',
        'email',
        'direccion',
        'centro_salud_id',
        'promotor_id',
        'fecha_enrolamiento',
        'paquete_id',
        'activo',
    ];

    public $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_enrolamiento' => 'date',
        'activo' => 'boolean',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function centroSalud()
    {
        return $this->belongsTo(CentroSalud::class);
    }

    public function promotor()
    {
        return $this->belongsTo(User::class, 'promotor_id');
    }

    public function paquete()
    {
        return $this->belongsTo(Paquete::class);
    }

    public function diagnosticos()
    {
        return $this->belongsToMany(Diagnostico::class, 'pacientes_diagnosticos');
    }
}
