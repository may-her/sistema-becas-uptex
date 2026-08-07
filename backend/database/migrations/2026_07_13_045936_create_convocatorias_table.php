<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('convocatorias', function (Blueprint $table) {

            $table->id();

            $table->foreignId('periodo_id')
                ->constrained('periodos')
                ->cascadeOnDelete();

            $table->string('nombre');

            $table->text('descripcion')->nullable();

            $table->text('requisitos');

            $table->decimal('promedio_minimo', 4, 2)
                ->default(8.00);

            $table->date('fecha_inicio');

            $table->date('fecha_cierre');

            $table->enum('estado', [
                'BORRADOR',
                'PUBLICADA',
                'CERRADA'
            ])->default('BORRADOR');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('convocatorias');
    }
};