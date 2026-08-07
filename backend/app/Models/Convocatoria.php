<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Convocatoria extends Model
{
    use HasFactory;

    protected $table = 'convocatorias';

    protected $fillable = [
        'periodo_id',
        'nombre',
        'descripcion',
        'requisitos',
        'promedio_minimo',
        'fecha_inicio',
        'fecha_cierre',
        'estado',
        'archivo',
    ];

    protected $casts = [
        'fecha_inicio' => 'date:Y-m-d',
        'fecha_cierre' => 'date:Y-m-d',
        'promedio_minimo' => 'decimal:2',
    ];

    protected $appends = [
        'archivo_url',
    ];

    public function getArchivoUrlAttribute(): ?string
    {
        if (!$this->archivo) {
            return null;
        }

        return url(Storage::url($this->archivo));
    }

    public function periodo()
    {
        return $this->belongsTo(
            Periodo::class,
            'periodo_id'
        );
    }

    public function solicitudes()
    {
        return $this->hasMany(
            Solicitud::class,
            'convocatoria_id'
        );
    }
}