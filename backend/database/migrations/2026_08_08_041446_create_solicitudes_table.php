<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('solicitudes')) {

            Schema::create('solicitudes', function (Blueprint $table) {

                $table->id();

                /*
                |--------------------------------------------------------------------------
                | ALUMNO
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();


                /*
                |--------------------------------------------------------------------------
                | CONVOCATORIA
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreignId('convocatoria_id')
                    ->constrained('convocatorias')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();


                /*
                |--------------------------------------------------------------------------
                | ESTADO DE LA SOLICITUD
                |--------------------------------------------------------------------------
                */

                $table->enum('estado', [
                    'PENDIENTE',
                    'EN_REVISION',
                    'DOCUMENTACION_INCOMPLETA',
                    'ACEPTADA',
                    'RECHAZADA',
                ])->default('PENDIENTE');


                /*
                |--------------------------------------------------------------------------
                | FOLIO
                |--------------------------------------------------------------------------
                */

                $table
                    ->string('folio', 50)
                    ->nullable()
                    ->unique();


                /*
                |--------------------------------------------------------------------------
                | OBSERVACIONES
                |--------------------------------------------------------------------------
                */

                $table
                    ->text('observaciones')
                    ->nullable();

                $table
                    ->text('comentario_revision')
                    ->nullable();


                /*
                |--------------------------------------------------------------------------
                | DICTAMEN
                |--------------------------------------------------------------------------
                */

                $table
                    ->timestamp('fecha_revision')
                    ->nullable();

                $table
                    ->foreignId('revisado_por')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();


                $table->timestamps();


                /*
                |--------------------------------------------------------------------------
                | Un alumno sólo debe tener una solicitud por convocatoria.
                |--------------------------------------------------------------------------
                */

                $table->unique([
                    'user_id',
                    'convocatoria_id'
                ]);
            });
        }
    }


    public function down(): void
    {
        Schema::dropIfExists('solicitudes');
    }
};