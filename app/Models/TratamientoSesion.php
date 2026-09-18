<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TratamientoSesion extends Model
{
    protected $table = 'tratamiento_sesiones';

    protected $fillable = ['tratamiento_version_id', 'semana', 'sesion', 'contenido'];

    protected function casts(): array
    {
        return ['contenido' => 'array'];
    }

    public function version()
    {
        return $this->belongsTo(TratamientoVersion::class, 'tratamiento_version_id');
    }

    public function multimedia()
    {
        return $this->hasMany(TratamientoMultimedia::class);
    }

    public function elementos()
    {
        return $this->hasMany(TratamientoElemento::class);
    }
}
