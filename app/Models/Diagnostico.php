<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Diagnostico extends Model
{
    public const DIABETES = 1;

    public const HIPERTENSION_ARTERIAL = 2;

    public const DEPRESION = 3;

    protected $fillable = [
        'nombre',
        'activo',
    ];

    public $timestamps = false;
}
