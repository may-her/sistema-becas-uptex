<?php

namespace App\Http\Controllers;

use App\Models\Convocatoria;
use App\Models\Solicitud;
use App\Notifications\ResultadoBecaNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ResultadosController extends Controller
{
    public function enviar(
        Convocatoria $convocatoria
    ): JsonResponse {
        /*
        |--------------------------------------------------------------------------
        | EVITAR DOBLE ENVÍO
        |--------------------------------------------------------------------------
        */

        if (
            $convocatoria->resultados_enviados_at
        ) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'Los resultados de esta convocatoria ya fueron enviados.',
            ], 409);
        }

        /*
        |--------------------------------------------------------------------------
        | OBTENER SOLAMENTE ACEPTADOS
        |--------------------------------------------------------------------------
        */

        $solicitudes = Solicitud::with(
            'user'
        )
            ->where(
                'convocatoria_id',
                $convocatoria->id
            )
            ->where(
                'estado',
                'ACEPTADA'
            )
            ->get();

        if ($solicitudes->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'No existen alumnos aceptados en esta convocatoria.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | ENVIAR
        |--------------------------------------------------------------------------
        */

        DB::transaction(
            function () use (
                $solicitudes,
                $convocatoria
            ) {
                foreach (
                    $solicitudes as $solicitud
                ) {
                    $usuario =
                        $solicitud->user;

                    if (
                        !$usuario ||
                        !$usuario->email
                    ) {
                        continue;
                    }

                    $usuario->notify(
                        new ResultadoBecaNotification(
                            $convocatoria,
                            $solicitud
                        )
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | MARCAR ENVÍO
                |--------------------------------------------------------------------------
                */

                $convocatoria
                    ->forceFill([
                        'resultados_enviados_at' =>
                            now(),
                    ])
                    ->save();
            }
        );

        return response()->json([
            'status' => 'success',

            'message' =>
                'Resultados enviados correctamente a los alumnos aceptados.',

            'total_enviados' =>
                $solicitudes->count(),

            'data' =>
                $convocatoria->fresh(),
        ]);
    }
}