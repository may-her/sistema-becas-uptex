<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asignacion extends Model
{
    use HasFactory;

    protected $table = 'asignaciones_carrera';

    protected $fillable = [
        'user_id',
        'carrera_id',
    ];

    public function usuario()
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    public function carrera()
    {
        return $this->belongsTo(
            Carrera::class,
            'carrera_id'
        );
    }
}