<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    protected $table = 'solicitudes';


    protected $fillable = [
        'user_id',
        'convocatoria_id',
        'carrera_id',
        'grupo_id',
        'grupo',
        'modalidad',
        'porcentaje_solicitado',
        'porcentaje_beca',
        'estado',
        'comentario_revision',
        'revisado_por',
        'fecha_revision',
        'resultado_enviado_at',
    ];


    protected $casts = [
        'porcentaje_solicitado' => 'decimal:2',
        'porcentaje_beca' => 'decimal:2',
        'fecha_revision' => 'datetime',
        'resultado_enviado_at' => 'datetime',
    ];


    /*
    |--------------------------------------------------------------------------
    | ALUMNO
    |--------------------------------------------------------------------------
    */

    public function usuario()
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
    | GRUPO
    |--------------------------------------------------------------------------
    */

    public function grupoRelacion()
    {
        return $this->belongsTo(
            Grupo::class,
            'grupo_id'
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
}