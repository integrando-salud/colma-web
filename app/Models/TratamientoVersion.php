<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TratamientoVersion extends Model
{
    protected $table = 'tratamiento_versiones';

    protected $fillable = ['pais', 'tipo', 'hash'];

    public function sesiones()
    {
        return $this->hasMany(TratamientoSesion::class);
    }

    public function entrenamientos()
    {
        return $this->hasMany(Entrenamiento::class);
    }
}
