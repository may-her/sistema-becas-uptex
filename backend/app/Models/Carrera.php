<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carrera extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'clave',
        'descripcion',
        'estado',
    ];

    public function usuarios()
    {
        return $this->hasMany(
            User::class
        );
    }

    public function alumnos()
    {
        return $this
            ->hasMany(User::class)
            ->where(
                'role',
                'alumno'
            );
    }

    public function grupos()
    {
        return $this->hasMany(
            Grupo::class
        );
    }
}