<?php

namespace App\Notifications;

use App\Models\Convocatoria;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConvocatoriaPorCerrarNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Convocatoria $convocatoria
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
        $fechaCierre =
            $this->convocatoria->fecha_cierre
                ? Carbon::parse(
                    $this->convocatoria->fecha_cierre
                )->format('d/m/Y')
                : 'Por consultar';

        $frontend =
            rtrim(
                env(
                    'FRONTEND_URL',
                    'http://localhost:5173'
                ),
                '/'
            );

        return (new MailMessage)
            ->subject(
                'La convocatoria de beca cierra en 2 días - UPTex'
            )
            ->greeting(
                'Hola ' .
                ($notifiable->name ?? 'estudiante')
            )
            ->line(
                'Te recordamos que faltan solamente 2 días para el cierre de la convocatoria.'
            )
            ->line(
                'Convocatoria: ' .
                $this->convocatoria->nombre
            )
            ->line(
                'Fecha límite: ' .
                $fechaCierre
            )
            ->action(
                'Ingresar al sistema',
                $frontend
            )
            ->line(
                'Completa tu solicitud antes de la fecha límite.'
            )
            ->line(
                'Si ya registraste tu solicitud, puedes ignorar este recordatorio.'
            )
            ->salutation(
                'Universidad Politécnica de Texcoco'
            );
    }

    public function toArray(
        object $notifiable
    ): array {
        return [
            'tipo' =>
                'CONVOCATORIA_POR_CERRAR',

            'convocatoria_id' =>
                $this->convocatoria->id,

            'nombre' =>
                $this->convocatoria->nombre,
        ];
    }
}