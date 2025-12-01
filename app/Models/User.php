<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Silber\Bouncer\Database\HasRolesAndAbilities;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasRolesAndAbilities, HasFactory, Notifiable, SoftDeletes;

    public const ROL_SUPERUSUARIO = 'superusuario';

    public const ROL_SUPERVISOR = 'supervisor';

    public const ROL_SUPERVISOR_PAIS = 'supervisor_pais';

    public const ROL_PROMOTOR = 'promotor';

    public const ROL_PACIENTE = 'paciente';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'sexo',
        'documento',
        'email',
        'password',
        'pais_id',
        'centro_salud_id',
        'supervisor_id',
        'accesos',
        'ultimo_acceso',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'ultimo_acceso' => 'datetime',
        ];
    }

    public function pais()
    {
        return $this->belongsTo(Pais::class);
    }

    public function centroSalud()
    {
        return $this->belongsTo(CentroSalud::class, 'centro_salud_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function promotores()
    {
        return $this->hasMany(User::class, 'supervisor_id');
    }

    public function pacientes()
    {
        return $this->hasMany(Paciente::class, 'promotor_id');
    }
}
