<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'carrera_id',
        'periodo_id',
        'tutor_id',
        'cuatrimestre',
        'turno',
        'estado',
    ];

    public function carrera()
    {
        return $this->belongsTo(
            Carrera::class
        );
    }

    public function periodo()
    {
        return $this->belongsTo(
            Periodo::class
        );
    }

    public function tutor()
    {
        return $this->belongsTo(
            User::class,
            'tutor_id'
        );
    }

    public function alumnos()
    {
        return $this->hasMany(
            User::class,
            'grupo_id'
        )
        ->where(
            'role',
            'alumno'
        );
    }
}