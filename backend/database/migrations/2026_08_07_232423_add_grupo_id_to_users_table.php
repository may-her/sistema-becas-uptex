<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            if (!Schema::hasColumn('users', 'grupo_id')) {

                $table
                    ->foreignId('grupo_id')
                    ->nullable()
                    ->after('carrera_id')
                    ->constrained('grupos')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            if (Schema::hasColumn('users', 'grupo_id')) {

                $table->dropForeign([
                    'grupo_id'
                ]);

                $table->dropColumn(
                    'grupo_id'
                );
            }
        });
    }
};