<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CentroSalud extends Model
{
    protected $table = 'centros_salud';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function pais()
    {
        return $this->belongsTo(Pais::class, 'pais_id');
    }
}
