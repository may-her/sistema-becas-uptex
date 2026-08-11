<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    protected $table = 'documentos_solicitud';

    protected $fillable = [
        'solicitud_id',
        'tipo_documento',
        'ruta_archivo',
        'nombre_original',
        'estado',
        'observaciones',
        'revisado_por',
        'revisado_at',
    ];

    protected $casts = [
        'revisado_at' => 'datetime',
    ];

    protected $appends = [
        'archivo_url',
    ];

    public function solicitud()
    {
        return $this->belongsTo(
            Solicitud::class,
            'solicitud_id'
        );
    }

    public function revisor()
    {
        return $this->belongsTo(
            User::class,
            'revisado_por'
        );
    }

    public function getArchivoUrlAttribute()
    {
        if (!$this->ruta_archivo) {
            return null;
        }

        return asset(
            'storage/' .
            ltrim(
                $this->ruta_archivo,
                '/'
            )
        );
    }
}