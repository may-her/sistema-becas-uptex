<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'carrera_id',
        'grupo',
        'matricula',
        'verification_code',
        'verification_code_expires_at',
    ];

    protected $hidden = [
        'password',
        'verification_code',
        'verification_code_expires_at',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verification_code_expires_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | CARRERA DEL USUARIO
    |--------------------------------------------------------------------------
    */

    public function carrera()
{
    return $this->belongsTo(
        \App\Models\Carrera::class
    );
}

public function grupoRelacion()
{
    return $this->belongsTo(
        \App\Models\Grupo::class,
        'grupo_id'
    );
}

public function gruposTutor()
{
    return $this->hasMany(
        \App\Models\Grupo::class,
        'tutor_id'
    );
}
    /*
    |--------------------------------------------------------------------------
    | SOLICITUDES DEL ALUMNO
    |--------------------------------------------------------------------------
    */

    public function solicitudes()
    {
        return $this->hasMany(
            Solicitud::class,
            'user_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CARRERAS ASIGNADAS
    |--------------------------------------------------------------------------
    |
    | Sirve para:
    | - Jefe de Carrera
    | - Profesor/Tutor
    |
    */

    public function carrerasAsignadas()
    {
        return $this->belongsToMany(
            Carrera::class,
            'asignaciones_carrera',
            'user_id',
            'carrera_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ASIGNACIONES
    |--------------------------------------------------------------------------
    */

    public function asignacionesCarrera()
    {
        return $this->hasMany(
            Asignacion::class,
            'user_id'
        );
    }
}