<?php

namespace App\Notifications;

use App\Models\Convocatoria;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConvocatoriaPublicadaNotification extends Notification
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
        $fechaInicio =
            $this->convocatoria->fecha_inicio
                ? Carbon::parse(
                    $this->convocatoria->fecha_inicio
                )->format('d/m/Y')
                : 'Por consultar';

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
                'Nueva convocatoria de beca disponible - UPTex'
            )
            ->greeting(
                'Hola ' .
                ($notifiable->name ?? 'estudiante')
            )
            ->line(
                'Se ha publicado una nueva convocatoria en el Sistema de Becas UPTex.'
            )
            ->line(
                'Convocatoria: ' .
                $this->convocatoria->nombre
            )
            ->line(
                'Fecha de inicio: ' .
                $fechaInicio
            )
            ->line(
                'Fecha de cierre: ' .
                $fechaCierre
            )
            ->line(
                'Promedio mínimo requerido: ' .
                $this->convocatoria->promedio_minimo
            )
            ->action(
                'Ingresar al Sistema de Becas',
                $frontend
            )
            ->line(
                'Revisa los requisitos y registra tu solicitud antes de la fecha límite.'
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
                'CONVOCATORIA_PUBLICADA',

            'convocatoria_id' =>
                $this->convocatoria->id,

            'nombre' =>
                $this->convocatoria->nombre,
        ];
    }
}