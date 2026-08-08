<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carreras', function (Blueprint $table) {

            if (!Schema::hasColumn('carreras', 'clave')) {
                $table
                    ->string('clave', 20)
                    ->nullable()
                    ->unique()
                    ->after('nombre');
            }

            if (!Schema::hasColumn('carreras', 'estado')) {
                $table
                    ->enum('estado', [
                        'ACTIVA',
                        'INACTIVA'
                    ])
                    ->default('ACTIVA');
            }

            if (!Schema::hasColumn('carreras', 'descripcion')) {
                $table
                    ->text('descripcion')
                    ->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('carreras', function (Blueprint $table) {

            if (Schema::hasColumn('carreras', 'clave')) {
                $table->dropUnique(['clave']);
                $table->dropColumn('clave');
            }

            if (Schema::hasColumn('carreras', 'estado')) {
                $table->dropColumn('estado');
            }

            if (Schema::hasColumn('carreras', 'descripcion')) {
                $table->dropColumn('descripcion');
            }
        });
    }
};