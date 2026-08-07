<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Rol extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'nombre',
        'descripcion'
    ];

    /**
     * Usuarios asociados a este rol a través de la tabla pivote.
     */
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'rol_asignaciones', 'rol_id', 'user_id')
                    ->withTimestamps();
    }
}