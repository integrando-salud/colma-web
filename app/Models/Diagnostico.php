<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Diagnostico extends Model
{
    public const int DIABETES = 1;

    public const int HIPERTENSION_ARTERIAL = 2;

    public const int DEPRESION = 3;

    protected $fillable = [
        'nombre',
        'activo',
    ];

    public $timestamps = false;
}
