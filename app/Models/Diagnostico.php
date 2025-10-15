<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Diagnostico extends Model
{
    protected $fillable = [
        'name',
        'active',
    ];

    public $timestamps = false;
}
