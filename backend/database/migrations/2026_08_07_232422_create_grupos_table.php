<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupos', function (Blueprint $table) {

            $table->id();

            $table
                ->string('nombre', 30);

            $table
                ->foreignId('carrera_id')
                ->constrained('carreras')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table
                ->foreignId('periodo_id')
                ->nullable()
                ->constrained('periodos')
                ->nullOnDelete();

            $table
                ->foreignId('tutor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table
                ->unsignedTinyInteger('cuatrimestre')
                ->nullable();

            $table
                ->enum('turno', [
                    'MATUTINO',
                    'VESPERTINO',
                    'MIXTO'
                ])
                ->default('MATUTINO');

            $table
                ->enum('estado', [
                    'ACTIVO',
                    'INACTIVO'
                ])
                ->default('ACTIVO');

            $table->timestamps();

            $table->unique([
                'nombre',
                'periodo_id'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grupos');
    }
};