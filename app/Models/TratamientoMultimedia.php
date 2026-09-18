<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TratamientoMultimedia extends Model
{
    protected $table = 'tratamiento_multimedia';

    protected $fillable = ['tratamiento_elemento_id', 'ruta', 'tipo', 'checksum'];

    public function elemento()
    {
        return $this->belongsTo(TratamientoElemento::class, 'tratamiento_elemento_id');
    }

}
