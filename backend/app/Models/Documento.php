<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    use HasFactory;

    protected $table = 'documentos_solicitud';

    protected $fillable = [
        'solicitud_id',
        'tipo_documento',
        'ruta_archivo',
        'nombre_original',
    ];

    public function solicitud()
    {
        return $this->belongsTo(
            Solicitud::class,
            'solicitud_id'
        );
    }
}