<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'must_change_password')) {
            Schema::table('users', function (Blueprint $table) {
                $table
                    ->boolean('must_change_password')
                    ->default(false);
            });
        }

        if (!Schema::hasColumn('solicitudes', 'porcentaje_aprobacion')) {
            Schema::table('solicitudes', function (Blueprint $table) {
                $table
                    ->decimal(
                        'porcentaje_aprobacion',
                        5,
                        2
                    )
                    ->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'must_change_password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(
                    'must_change_password'
                );
            });
        }

        if (Schema::hasColumn('solicitudes', 'porcentaje_aprobacion')) {
            Schema::table('solicitudes', function (Blueprint $table) {
                $table->dropColumn(
                    'porcentaje_aprobacion'
                );
            });
        }
    }
};