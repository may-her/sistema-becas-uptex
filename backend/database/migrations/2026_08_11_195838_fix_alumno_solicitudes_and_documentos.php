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
        | SOLICITUDES
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'solicitudes',
            function (Blueprint $table) {

                if (
                    !Schema::hasColumn(
                        'solicitudes',
                        'grupo'
                    )
                ) {
                    $table
                        ->string(
                            'grupo',
                            50
                        )
                        ->nullable();
                }


                if (
                    !Schema::hasColumn(
                        'solicitudes',
                        'grupo_id'
                    )
                ) {
                    $table
                        ->unsignedBigInteger(
                            'grupo_id'
                        )
                        ->nullable();
                }


                if (
                    !Schema::hasColumn(
                        'solicitudes',
                        'modalidad'
                    )
                ) {
                    $table
                        ->string(
                            'modalidad',
                            60
                        )
                        ->nullable();
                }


                if (
                    !Schema::hasColumn(
                        'solicitudes',
                        'porcentaje_solicitado'
                    )
                ) {
                    $table
                        ->decimal(
                            'porcentaje_solicitado',
                            5,
                            2
                        )
                        ->nullable();
                }


                if (
                    !Schema::hasColumn(
                        'solicitudes',
                        'porcentaje_beca'
                    )
                ) {
                    $table
                        ->decimal(
                            'porcentaje_beca',
                            5,
                            2
                        )
                        ->nullable();
                }


                if (
                    !Schema::hasColumn(
                        'solicitudes',
                        'resultado_enviado_at'
                    )
                ) {
                    $table
                        ->timestamp(
                            'resultado_enviado_at'
                        )
                        ->nullable();
                }
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Convertimos modalidad a VARCHAR
        |--------------------------------------------------------------------------
        |
        | Esto evita tener que modificar un ENUM cada vez
        | que agreguemos un nuevo tipo de beca.
        |
        */

        if (
            Schema::hasColumn(
                'solicitudes',
                'modalidad'
            )
        ) {
            DB::statement(
                "
                ALTER TABLE solicitudes
                MODIFY modalidad VARCHAR(60) NULL
                "
            );
        }


        /*
        |--------------------------------------------------------------------------
        | DOCUMENTOS
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'documentos_solicitud',
            function (Blueprint $table) {

                if (
                    !Schema::hasColumn(
                        'documentos_solicitud',
                        'estado'
                    )
                ) {
                    $table
                        ->string(
                            'estado',
                            30
                        )
                        ->default(
                            'PENDIENTE'
                        );
                }


                if (
                    !Schema::hasColumn(
                        'documentos_solicitud',
                        'observaciones'
                    )
                ) {
                    $table
                        ->text(
                            'observaciones'
                        )
                        ->nullable();
                }


                if (
                    !Schema::hasColumn(
                        'documentos_solicitud',
                        'revisado_por'
                    )
                ) {
                    $table
                        ->unsignedBigInteger(
                            'revisado_por'
                        )
                        ->nullable();
                }


                if (
                    !Schema::hasColumn(
                        'documentos_solicitud',
                        'revisado_at'
                    )
                ) {
                    $table
                        ->timestamp(
                            'revisado_at'
                        )
                        ->nullable();
                }
            }
        );


        /*
        |--------------------------------------------------------------------------
        | REPARAR FOREIGN KEY DE DOCUMENTOS
        |--------------------------------------------------------------------------
        |
        | Buscamos automáticamente la llave extranjera
        | de solicitud_id y, si apunta a solicitudes_beca,
        | la cambiamos a solicitudes.
        |
        */

        $database =
            DB::getDatabaseName();


        $foreign =
            DB::selectOne(
                "
                SELECT
                    CONSTRAINT_NAME,
                    REFERENCED_TABLE_NAME

                FROM information_schema.KEY_COLUMN_USAGE

                WHERE
                    TABLE_SCHEMA = ?
                    AND TABLE_NAME = 'documentos_solicitud'
                    AND COLUMN_NAME = 'solicitud_id'
                    AND REFERENCED_TABLE_NAME IS NOT NULL

                LIMIT 1
                ",
                [
                    $database
                ]
            );


        if (
            $foreign &&
            $foreign->REFERENCED_TABLE_NAME !==
            'solicitudes'
        ) {
            DB::statement(
                "
                ALTER TABLE documentos_solicitud
                DROP FOREIGN KEY `{$foreign->CONSTRAINT_NAME}`
                "
            );


            DB::statement(
                "
                ALTER TABLE documentos_solicitud

                ADD CONSTRAINT documentos_solicitud_solicitud_id_foreign

                FOREIGN KEY (solicitud_id)

                REFERENCES solicitudes(id)

                ON DELETE CASCADE

                ON UPDATE CASCADE
                "
            );
        }
    }


    public function down(): void
    {
        /*
         * No hacemos rollback destructivo porque
         * estas columnas pueden contener solicitudes
         * y documentos reales.
         */
    }
};