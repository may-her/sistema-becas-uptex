<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {

            if (!Schema::hasColumn('solicitudes', 'modalidad')) {
                $table->enum('modalidad', [
                    'DISCAPACIDAD',
                    'EXCELENCIA_ACADEMICA',
                    'SITUACION_SOCIOECONOMICA',
                ])->nullable();
            }

            if (!Schema::hasColumn('solicitudes', 'carrera_id')) {
                $table->foreignId('carrera_id')
                    ->nullable()
                    ->constrained('carreras')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('solicitudes', 'grupo_id')) {
                $table->foreignId('grupo_id')
                    ->nullable()
                    ->constrained('grupos')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {

            if (Schema::hasColumn('solicitudes', 'grupo_id')) {
                $table->dropForeign(['grupo_id']);
                $table->dropColumn('grupo_id');
            }

            if (Schema::hasColumn('solicitudes', 'carrera_id')) {
                $table->dropForeign(['carrera_id']);
                $table->dropColumn('carrera_id');
            }

            if (Schema::hasColumn('solicitudes', 'modalidad')) {
                $table->dropColumn('modalidad');
            }
        });
    }
};