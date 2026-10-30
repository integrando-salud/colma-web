<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CentroSalud extends Model
{
    protected $table = 'centros_salud';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'pais_id',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function pais()
    {
        return $this->belongsTo(Pais::class, 'pais_id');
    }

    public function pacientes()
    {
        return $this->hasMany(Paciente::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
