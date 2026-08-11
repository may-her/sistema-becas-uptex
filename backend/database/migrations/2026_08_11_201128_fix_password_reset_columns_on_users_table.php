<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | CREAR COLUMNAS SI NO EXISTEN
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'users',
            function (
                Blueprint $table
            ) {

                if (
                    !Schema::hasColumn(
                        'users',
                        'reset_password_code'
                    )
                ) {
                    $table
                        ->string(
                            'reset_password_code',
                            255
                        )
                        ->nullable();
                }


                if (
                    !Schema::hasColumn(
                        'users',
                        'reset_password_expires_at'
                    )
                ) {
                    $table
                        ->timestamp(
                            'reset_password_expires_at'
                        )
                        ->nullable();
                }


                if (
                    !Schema::hasColumn(
                        'users',
                        'debe_cambiar_password'
                    )
                ) {
                    $table
                        ->boolean(
                            'debe_cambiar_password'
                        )
                        ->default(false);
                }


                if (
                    !Schema::hasColumn(
                        'users',
                        'password_temporal_generada_at'
                    )
                ) {
                    $table
                        ->timestamp(
                            'password_temporal_generada_at'
                        )
                        ->nullable();
                }
            }
        );


        /*
        |--------------------------------------------------------------------------
        | ASEGURAR QUE EL CÓDIGO PUEDA GUARDAR HASH
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasColumn(
                'users',
                'reset_password_code'
            )
        ) {
            DB::statement(
                "
                ALTER TABLE users
                MODIFY reset_password_code
                VARCHAR(255) NULL
                "
            );
        }
    }


    public function down(): void
    {
        /*
         * No hacemos rollback destructivo.
         */
    }
};