<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TratamientoMultimedia extends Model
{
    protected $table = 'tratamiento_multimedia';

    protected $fillable = ['tratamiento_sesion_id', 'ruta', 'tipo', 'checksum'];

    public function sesion()
    {
        return $this->belongsTo(TratamientoSesion::class, 'tratamiento_sesion_id');
    }

    public function lecturas()
    {
        return $this->hasMany(MultimediaRead::class);
    }
}
