<?php

namespace App\Console\Commands;

use App\Models\Convocatoria;
use App\Models\User;
use App\Notifications\ConvocatoriaPorCerrarNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class EnviarRecordatoriosConvocatoria extends Command
{
    protected $signature =
        'becas:recordar-cierre';

    protected $description =
        'Envía recordatorios dos días antes del cierre de convocatorias';


    public function handle(): int
    {
        $fechaObjetivo =
            now()
                ->addDays(2)
                ->toDateString();


        $convocatorias =
            Convocatoria::query()
                ->where(
                    'estado',
                    'PUBLICADA'
                )
                ->whereDate(
                    'fecha_cierre',
                    $fechaObjetivo
                )
                ->whereNull(
                    'recordatorio_2_dias_en'
                )
                ->get();


        foreach (
            $convocatorias as
            $convocatoria
        ) {

            /*
             * LOCAL
             */

            if (
                app()->environment(
                    'local'
                )
            ) {
                $correo =
                    env('MAIL_TEST_TO');


                if (!$correo) {
                    $this->error(
                        'MAIL_TEST_TO no está configurado.'
                    );

                    continue;
                }


                $alumno =
                    User::query()
                        ->where(
                            'role',
                            'alumno'
                        )
                        ->where(
                            'email',
                            $correo
                        )
                        ->first();


                if (!$alumno) {
                    $this->error(
                        "No existe alumno {$correo}."
                    );

                    continue;
                }


                Notification::send(
                    $alumno,
                    new ConvocatoriaPorCerrarNotification(
                        $convocatoria
                    )
                );

            } else {

                /*
                 * PRODUCCIÓN
                 */

                User::query()
                    ->where(
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
                        function (
                            $alumnos
                        ) use (
                            $convocatoria
                        ) {
                            Notification::send(
                                $alumnos,
                                new ConvocatoriaPorCerrarNotification(
                                    $convocatoria
                                )
                            );
                        }
                    );
            }


            /*
             * Marcar solamente después
             * de que no hubo excepción.
             */

            $convocatoria
                ->forceFill([
                    'recordatorio_2_dias_en'
                        => now(),
                ])
                ->save();


            $this->info(
                'Recordatorio enviado: ' .
                $convocatoria->nombre
            );
        }


        return self::SUCCESS;
    }
}