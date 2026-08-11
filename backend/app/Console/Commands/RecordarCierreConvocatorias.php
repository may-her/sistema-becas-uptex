<?php

namespace App\Console\Commands;

use App\Models\Convocatoria;
use App\Models\User;
use App\Notifications\ConvocatoriaPorCerrarNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class RecordarCierreConvocatorias extends Command
{
    /**
     * Nombre del comando Artisan.
     *
     * Se ejecutará así:
     *
     * php artisan convocatorias:recordar-cierre
     */
    protected $signature = 'convocatorias:recordar-cierre';

    /**
     * Descripción del comando.
     */
    protected $description =
        'Envía un recordatorio a los alumnos cuando faltan 2 días para cerrar una convocatoria';

    /**
     * Ejecutar comando.
     */
    public function handle(): int
    {
        $hoy = Carbon::today();

        /*
        |--------------------------------------------------------------------------
        | Buscar convocatorias publicadas
        |--------------------------------------------------------------------------
        |
        | Solo buscamos:
        |
        | - PUBLICADAS
        | - que todavía NO hayan enviado el recordatorio
        |
        */

        $convocatorias = Convocatoria::where(
            'estado',
            'PUBLICADA'
        )
            ->whereNull(
                'recordatorio_2_dias_en'
            )
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Revisar cada convocatoria
        |--------------------------------------------------------------------------
        */

        foreach ($convocatorias as $convocatoria) {

            /*
            |--------------------------------------------------------------------------
            | Si no tiene fecha de cierre, ignorarla
            |--------------------------------------------------------------------------
            */

            if (!$convocatoria->fecha_cierre) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Convertir fecha de cierre
            |--------------------------------------------------------------------------
            */

            $fechaCierre = Carbon::parse(
                $convocatoria->fecha_cierre
            )->startOfDay();

            /*
            |--------------------------------------------------------------------------
            | Calcular días faltantes
            |--------------------------------------------------------------------------
            */

            $diasRestantes = $hoy->diffInDays(
                $fechaCierre,
                false
            );

            /*
            |--------------------------------------------------------------------------
            | Solo enviar cuando falten EXACTAMENTE 2 días
            |--------------------------------------------------------------------------
            */

            if ($diasRestantes !== 2) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Obtener alumnos
            |--------------------------------------------------------------------------
            */

            User::where(
                'role',
                'alumno'
            )
                ->whereNotNull(
                    'email'
                )
                ->where(
                    'email',
                    '!=',
                    ''
                )
                ->chunkById(
                    100,
                    function ($alumnos) use ($convocatoria) {

                        /*
                        |--------------------------------------------------------------------------
                        | Enviar notificación por correo
                        |--------------------------------------------------------------------------
                        */

                        Notification::send(
                            $alumnos,
                            new ConvocatoriaPorCerrarNotification(
                                $convocatoria
                            )
                        );
                    }
                );

            /*
            |--------------------------------------------------------------------------
            | Marcar que el recordatorio ya fue enviado
            |--------------------------------------------------------------------------
            |
            | Esto evita que se mande otra vez al día siguiente
            | o si el comando se ejecuta varias veces.
            |
            */

            $convocatoria->forceFill([
                'recordatorio_2_dias_en' => now(),
            ])->save();

            /*
            |--------------------------------------------------------------------------
            | Mensaje en consola
            |--------------------------------------------------------------------------
            */

            $this->info(
                'Recordatorio enviado para la convocatoria: ' .
                $convocatoria->nombre
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Si no hay errores
        |--------------------------------------------------------------------------
        */

        $this->info(
            'Revisión de convocatorias terminada.'
        );

        return self::SUCCESS;
    }
}