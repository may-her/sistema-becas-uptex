<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_beca';

    protected $fillable = [
        'user_id',
        'convocatoria_id',
        'carrera_id',
        'grupo',
        'estatus',
        'comentario_revision',
        'revisado_por',
        'revisado_at',
    ];

    protected $casts = [
        'revisado_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | ALUMNO
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CONVOCATORIA
    |--------------------------------------------------------------------------
    */

    public function convocatoria()
    {
        return $this->belongsTo(
            Convocatoria::class,
            'convocatoria_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CARRERA
    |--------------------------------------------------------------------------
    */

    public function carrera()
    {
        return $this->belongsTo(
            Carrera::class,
            'carrera_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | USUARIO QUE REVISÓ
    |--------------------------------------------------------------------------
    */

    public function revisor()
    {
        return $this->belongsTo(
            User::class,
            'revisado_por'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DOCUMENTOS
    |--------------------------------------------------------------------------
    */

    public function documentos()
    {
        return $this->hasMany(
            Documento::class,
            'solicitud_id'
        );
    }
}