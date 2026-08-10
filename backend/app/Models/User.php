<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'carrera_id',
        'grupo',
        'grupo_id',
        'matricula',
        'verification_code',
        'verification_code_expires_at',
    ];

    protected $hidden = [
        'password',
        'verification_code',
        'verification_code_expires_at',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' =>
                'datetime',

            'verification_code_expires_at' =>
                'datetime',

            'two_factor_confirmed_at' =>
                'datetime',

            'password' =>
                'hashed',
        ];
    }

    public function carrera()
    {
        return $this->belongsTo(
            Carrera::class
        );
    }

    public function grupoRelacion()
    {
        return $this->belongsTo(
            Grupo::class,
            'grupo_id'
        );
    }

    public function gruposTutor()
    {
        return $this->hasMany(
            Grupo::class,
            'tutor_id'
        );
    }

    public function solicitudes()
    {
        return $this->hasMany(
            Solicitud::class,
            'user_id'
        );
    }

    public function carrerasAsignadas()
    {
        return $this->belongsToMany(
            Carrera::class,
            'asignaciones_carrera',
            'user_id',
            'carrera_id'
        );
    }

    public function asignacionesCarrera()
    {
        return $this->hasMany(
            Asignacion::class,
            'user_id'
        );
    }
}