<?php

namespace App\Notifications;

use App\Models\Convocatoria;
use App\Models\Solicitud;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResultadoBecaNotification extends Notification
{
    use Queueable;

    public function __construct(
        private Convocatoria $convocatoria,
        private Solicitud $solicitud
    ) {
    }

    public function via(
        object $notifiable
    ): array {
        return ['mail'];
    }

    public function toMail(
        object $notifiable
    ): MailMessage {
        $frontend =
            rtrim(
                env(
                    'FRONTEND_URL',
                    'http://localhost:5173'
                ),
                '/'
            );

        $estado =
            strtoupper(
                (string)
                $this->solicitud->estado
            );

        $aceptada =
            $estado === 'ACEPTADA';

        $mensaje =
            $aceptada
                ? 'Tu solicitud de beca fue aceptada.'
                : 'Tu solicitud de beca no fue aprobada en esta convocatoria.';

        $mail =
            (new MailMessage)
                ->subject(
                    'Resultado de solicitud de beca - UPTex'
                )
                ->greeting(
                    'Hola ' .
                    ($notifiable->name ?? 'estudiante')
                )
                ->line(
                    'Tenemos disponible el resultado de tu solicitud de beca.'
                )
                ->line(
                    'Convocatoria: ' .
                    $this->convocatoria->nombre
                )
                ->line(
                    'Folio: ' .
                    (
                        $this->solicitud->folio
                        ??
                        $this->solicitud->id
                    )
                )
                ->line(
                    'Resultado: ' .
                    $estado
                )
                ->line(
                    $mensaje
                );

        if (
            $aceptada &&
            $this->solicitud
                ->porcentaje_beca !== null
        ) {
            $mail->line(
                'Porcentaje de beca autorizado: ' .
                $this->solicitud
                    ->porcentaje_beca .
                '%'
            );
        }

        if (
            $this->solicitud
                ->comentario_revision
        ) {
            $mail->line(
                'Observaciones: ' .
                $this->solicitud
                    ->comentario_revision
            );
        }

        return $mail
            ->action(
                'Ingresar al sistema',
                $frontend
            )
            ->salutation(
                'Universidad Politécnica de Texcoco'
            );
    }

    public function toArray(
        object $notifiable
    ): array {
        return [
            'convocatoria_id' =>
                $this->convocatoria->id,

            'solicitud_id' =>
                $this->solicitud->id,

            'estado' =>
                $this->solicitud->estado,

            'porcentaje_beca' =>
                $this->solicitud
                    ->porcentaje_beca,

            'tipo' =>
                'RESULTADO_BECA',
        ];
    }
}