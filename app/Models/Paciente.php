<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Paciente extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'user_id',
        'nombre',
        'apellido',
        'fecha_nacimiento',
        'sexo',
        'documento',
        'telefono',
        'telefono2',
        'email',
        'direccion',
        'referencia',
        'observaciones',
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
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
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

    public function glucosa()
    {
        return $this->hasMany(Glucosa::class);
    }

    public function presionArterial()
    {
        return $this->hasMany(PresionArterial::class);
    }

    public function estadoAnimo()
    {
        return $this->hasMany(EstadoAnimo::class);
    }

    public function interacciones()
    {
        return $this->hasMany(Interaccion::class);
    }

    public function alertas()
    {
        return $this->hasMany(Alerta::class);
    }
}
