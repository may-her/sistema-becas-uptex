<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('convocatorias', function (Blueprint $table) {

            if (!Schema::hasColumn(
                'convocatorias',
                'notificacion_publicada_en'
            )) {
                $table
                    ->timestamp('notificacion_publicada_en')
                    ->nullable();
            }

        });
    }

    public function down(): void
    {
        Schema::table('convocatorias', function (Blueprint $table) {

            if (Schema::hasColumn(
                'convocatorias',
                'notificacion_publicada_en'
            )) {
                $table->dropColumn(
                    'notificacion_publicada_en'
                );
            }

        });
    }
};