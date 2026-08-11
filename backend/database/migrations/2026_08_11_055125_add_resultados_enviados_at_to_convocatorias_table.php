<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'convocatorias',
            function (
                Blueprint $table
            ) {
                if (
                    !Schema::hasColumn(
                        'convocatorias',
                        'resultados_enviados_at'
                    )
                ) {
                    $table
                        ->timestamp(
                            'resultados_enviados_at'
                        )
                        ->nullable();
                }
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'convocatorias',
            function (
                Blueprint $table
            ) {
                if (
                    Schema::hasColumn(
                        'convocatorias',
                        'resultados_enviados_at'
                    )
                ) {
                    $table
                        ->dropColumn(
                            'resultados_enviados_at'
                        );
                }
            }
        );
    }
};