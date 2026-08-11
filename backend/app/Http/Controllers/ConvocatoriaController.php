<?php

namespace App\Http\Controllers;

use App\Models\Convocatoria;
use App\Models\User;
use App\Notifications\ConvocatoriaCerradaNotification;
use App\Notifications\ConvocatoriaPublicadaNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class ConvocatoriaController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LISTAR CONVOCATORIAS
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $convocatorias =
            Convocatoria::query()
                ->with('periodo')
                ->orderByDesc('id')
                ->get();

        return response()->json([
            'status' => 'success',
            'data' => $convocatorias,
            'convocatorias' => $convocatorias,
        ], 200);
    }


    /*
    |--------------------------------------------------------------------------
    | VER UNA CONVOCATORIA
    |--------------------------------------------------------------------------
    */

    public function show(
        Convocatoria $convocatoria
    ) {
        $convocatoria->load(
            'periodo'
        );

        return response()->json([
            'status' => 'success',
            'data' => $convocatoria,
            'convocatoria' => $convocatoria,
        ], 200);
    }


    /*
    |--------------------------------------------------------------------------
    | CONVOCATORIA PÚBLICA
    |--------------------------------------------------------------------------
    */

    public function publica()
    {
        $convocatoria =
            $this->buscarConvocatoriaVigente();

        return response()->json([
            'status' => 'success',

            'data' =>
                $convocatoria,

            'convocatoria' =>
                $convocatoria,

            'convocatorias' =>
                $convocatoria
                    ? [$convocatoria]
                    : [],
        ], 200);
    }


    /*
    |--------------------------------------------------------------------------
    | CONVOCATORIA VIGENTE
    |--------------------------------------------------------------------------
    */

    public function obtenerVigente()
    {
        $convocatoria =
            $this->buscarConvocatoriaVigente();

        return response()->json([
            'status' => 'success',
            'data' => $convocatoria,
            'convocatoria' => $convocatoria,
        ], 200);
    }


    /*
    |--------------------------------------------------------------------------
    | ALIAS ACTIVA
    |--------------------------------------------------------------------------
    */

    public function getActiva()
    {
        return $this
            ->obtenerVigente();
    }


    /*
    |--------------------------------------------------------------------------
    | CONVOCATORIA PARA ALUMNO
    |--------------------------------------------------------------------------
    */

    public function actual()
    {
        return $this
            ->obtenerVigente();
    }


    /*
    |--------------------------------------------------------------------------
    | CREAR CONVOCATORIA
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request
    ) {
        $validated =
            $request->validate([
                'periodo_id' => [
                    'required',
                    'integer',
                    'exists:periodos,id',
                ],

                'nombre' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'descripcion' => [
                    'nullable',
                    'string',
                ],

                'requisitos' => [
                    'required',
                    'string',
                ],

                'promedio_minimo' => [
                    'required',
                    'numeric',
                    'min:0',
                    'max:10',
                ],

                'fecha_inicio' => [
                    'required',
                    'date',
                ],

                'fecha_cierre' => [
                    'required',
                    'date',
                    'after_or_equal:fecha_inicio',
                ],

                'estado' => [
                    'required',
                    'in:BORRADOR,PUBLICADA,CERRADA',
                ],

                'archivo' => [
                    'nullable',
                    'file',
                    'mimes:pdf',
                    'max:10240',
                ],
            ]);


        /*
        |--------------------------------------------------------------------------
        | EVITAR CREACIÓN DUPLICADA
        |--------------------------------------------------------------------------
        */

        $duplicada =
            Convocatoria::query()
                ->where(
                    'periodo_id',
                    $validated['periodo_id']
                )
                ->where(
                    'nombre',
                    $validated['nombre']
                )
                ->where(
                    'fecha_inicio',
                    $validated['fecha_inicio']
                )
                ->where(
                    'fecha_cierre',
                    $validated['fecha_cierre']
                )
                ->where(
                    'created_at',
                    '>=',
                    now()->subSeconds(15)
                )
                ->orderByDesc('id')
                ->first();


        if ($duplicada) {
            return response()->json([
                'status' => 'success',

                'message' =>
                    'La convocatoria ya había sido registrada.',

                'data' =>
                    $duplicada
                        ->load('periodo'),

                'convocatoria' =>
                    $duplicada,
            ], 200);
        }


        /*
        |--------------------------------------------------------------------------
        | SUBIR PDF
        |--------------------------------------------------------------------------
        */

        if (
            $request->hasFile(
                'archivo'
            )
        ) {
            $validated['archivo'] =
                $request
                    ->file('archivo')
                    ->store(
                        'convocatorias',
                        'public'
                    );
        }


        $rutaArchivo =
            $validated['archivo'] ??
            null;


        /*
        |--------------------------------------------------------------------------
        | CREAR REGISTRO
        |--------------------------------------------------------------------------
        */

        try {

            $convocatoria =
                DB::transaction(
                    function () use (
                        $validated
                    ) {

                        /*
                        |--------------------------------------------------------------------------
                        | UNA SOLA CONVOCATORIA PUBLICADA
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $validated['estado']
                            === 'PUBLICADA'
                        ) {
                            Convocatoria::query()
                                ->where(
                                    'estado',
                                    'PUBLICADA'
                                )
                                ->update([
                                    'estado' =>
                                        'CERRADA',
                                ]);
                        }


                        return Convocatoria::create(
                            $validated
                        );
                    }
                );

        } catch (\Throwable $e) {

            if (
                $rutaArchivo &&
                Storage::disk('public')
                    ->exists(
                        $rutaArchivo
                    )
            ) {
                Storage::disk('public')
                    ->delete(
                        $rutaArchivo
                    );
            }


            report($e);


            return response()->json([
                'status' => 'error',

                'message' =>
                    'No fue posible crear la convocatoria.',

                'error' =>
                    config('app.debug')
                        ? $e->getMessage()
                        : null,
            ], 500);
        }


        /*
        |--------------------------------------------------------------------------
        | SI SE CREÓ COMO PUBLICADA, NOTIFICAR
        |--------------------------------------------------------------------------
        */

        if (
            strtoupper(
                (string)
                $convocatoria->estado
            ) === 'PUBLICADA'
        ) {

            try {

                $this->notificarPublicacion(
                    $convocatoria
                );

            } catch (\Throwable $e) {

                report($e);


                /*
                 * La convocatoria se queda creada.
                 * No hacemos rollback por fallo del correo.
                 */

                return response()->json([
                    'status' => 'warning',

                    'message' =>
                        'La convocatoria fue creada y publicada, pero uno o más correos no pudieron enviarse.',

                    'mail_error' =>
                        config('app.debug')
                            ? $e->getMessage()
                            : null,

                    'data' =>
                        $convocatoria
                            ->fresh('periodo'),

                    'convocatoria' =>
                        $convocatoria
                            ->fresh('periodo'),
                ], 201);
            }
        }


        return response()->json([
            'status' => 'success',

            'message' =>
                $convocatoria->estado ===
                'PUBLICADA'
                    ? 'Convocatoria creada, publicada y alumnos notificados.'
                    : 'Convocatoria creada correctamente.',

            'data' =>
                $convocatoria
                    ->fresh('periodo'),

            'convocatoria' =>
                $convocatoria
                    ->fresh('periodo'),
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR CONVOCATORIA
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        Convocatoria $convocatoria
    ) {
        $estadoAnterior =
            strtoupper(
                (string)
                $convocatoria->estado
            );


        $rutaAnterior =
            $convocatoria->archivo;


        $validated =
            $request->validate([
                'periodo_id' => [
                    'sometimes',
                    'required',
                    'integer',
                    'exists:periodos,id',
                ],

                'nombre' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:255',
                ],

                'descripcion' => [
                    'sometimes',
                    'nullable',
                    'string',
                ],

                'requisitos' => [
                    'sometimes',
                    'required',
                    'string',
                ],

                'promedio_minimo' => [
                    'sometimes',
                    'required',
                    'numeric',
                    'min:0',
                    'max:10',
                ],

                'fecha_inicio' => [
                    'sometimes',
                    'required',
                    'date',
                ],

                'fecha_cierre' => [
                    'sometimes',
                    'required',
                    'date',
                ],

                'estado' => [
                    'sometimes',
                    'required',
                    'in:BORRADOR,PUBLICADA,CERRADA',
                ],

                'archivo' => [
                    'sometimes',
                    'nullable',
                    'file',
                    'mimes:pdf',
                    'max:10240',
                ],
            ]);


        /*
        |--------------------------------------------------------------------------
        | VALIDAR FECHAS
        |--------------------------------------------------------------------------
        */

        $fechaInicio =
            $validated['fecha_inicio']
            ??
            $convocatoria->fecha_inicio;


        $fechaCierre =
            $validated['fecha_cierre']
            ??
            $convocatoria->fecha_cierre;


        if (
            $fechaInicio &&
            $fechaCierre &&
            strtotime(
                (string)
                $fechaCierre
            )
            <
            strtotime(
                (string)
                $fechaInicio
            )
        ) {
            return response()->json([
                'status' => 'error',

                'message' =>
                    'La fecha de cierre no puede ser anterior a la fecha de inicio.',
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | PDF NUEVO
        |--------------------------------------------------------------------------
        */

        $rutaNueva =
            null;


        if (
            $request->hasFile(
                'archivo'
            )
        ) {
            $rutaNueva =
                $request
                    ->file('archivo')
                    ->store(
                        'convocatorias',
                        'public'
                    );


            $validated['archivo'] =
                $rutaNueva;
        }


        try {

            DB::transaction(
                function () use (
                    $validated,
                    $convocatoria
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | SI SE PUBLICA, CERRAR LAS DEMÁS
                    |--------------------------------------------------------------------------
                    */

                    if (
                        isset(
                            $validated['estado']
                        )
                        &&
                        $validated['estado']
                        === 'PUBLICADA'
                    ) {
                        Convocatoria::query()
                            ->where(
                                'id',
                                '!=',
                                $convocatoria->id
                            )
                            ->where(
                                'estado',
                                'PUBLICADA'
                            )
                            ->update([
                                'estado' =>
                                    'CERRADA',
                            ]);
                    }


                    $convocatoria->update(
                        $validated
                    );
                }
            );

        } catch (\Throwable $e) {

            if (
                $rutaNueva &&
                Storage::disk('public')
                    ->exists(
                        $rutaNueva
                    )
            ) {
                Storage::disk('public')
                    ->delete(
                        $rutaNueva
                    );
            }


            report($e);


            return response()->json([
                'status' => 'error',

                'message' =>
                    'No fue posible actualizar la convocatoria.',

                'error' =>
                    config('app.debug')
                        ? $e->getMessage()
                        : null,
            ], 500);
        }


        /*
        |--------------------------------------------------------------------------
        | BORRAR PDF VIEJO
        |--------------------------------------------------------------------------
        */

        if (
            $rutaNueva &&
            $rutaAnterior &&
            $rutaAnterior !==
            $rutaNueva &&
            Storage::disk('public')
                ->exists(
                    $rutaAnterior
                )
        ) {
            Storage::disk('public')
                ->delete(
                    $rutaAnterior
                );
        }


        $convocatoria->refresh();


        $estadoNuevo =
            strtoupper(
                (string)
                $convocatoria->estado
            );


        /*
        |--------------------------------------------------------------------------
        | CAMBIÓ A PUBLICADA
        |--------------------------------------------------------------------------
        */

        if (
            $estadoAnterior !==
            'PUBLICADA'
            &&
            $estadoNuevo ===
            'PUBLICADA'
        ) {

            try {

                $this->notificarPublicacion(
                    $convocatoria
                );

            } catch (\Throwable $e) {

                report($e);


                return response()->json([
                    'status' => 'warning',

                    'message' =>
                        'La convocatoria fue actualizada y publicada, pero uno o más correos no pudieron enviarse.',

                    'mail_error' =>
                        config('app.debug')
                            ? $e->getMessage()
                            : null,

                    'data' =>
                        $convocatoria
                            ->fresh('periodo'),
                ], 200);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | CAMBIÓ A CERRADA
        |--------------------------------------------------------------------------
        */

        if (
            $estadoAnterior !==
            'CERRADA'
            &&
            $estadoNuevo ===
            'CERRADA'
        ) {

            try {

                $this->notificarCierre(
                    $convocatoria
                );

            } catch (\Throwable $e) {

                report($e);


                return response()->json([
                    'status' => 'warning',

                    'message' =>
                        'La convocatoria fue cerrada, pero uno o más correos no pudieron enviarse.',

                    'mail_error' =>
                        config('app.debug')
                            ? $e->getMessage()
                            : null,

                    'data' =>
                        $convocatoria
                            ->fresh('periodo'),
                ], 200);
            }
        }


        return response()->json([
            'status' => 'success',

            'message' =>
                'Convocatoria actualizada correctamente.',

            'data' =>
                $convocatoria
                    ->fresh('periodo'),
        ], 200);
    }


    /*
    |--------------------------------------------------------------------------
    | REEMPLAZAR PDF
    |--------------------------------------------------------------------------
    */

    public function reemplazarArchivo(
        Request $request,
        Convocatoria $convocatoria
    ) {
        $request->validate([
            'archivo' => [
                'required',
                'file',
                'mimes:pdf',
                'max:10240',
            ],
        ]);


        $rutaNueva =
            $request
                ->file('archivo')
                ->store(
                    'convocatorias',
                    'public'
                );


        $rutaAnterior =
            $convocatoria->archivo;


        try {

            $convocatoria->update([
                'archivo' =>
                    $rutaNueva,
            ]);

        } catch (\Throwable $e) {

            Storage::disk('public')
                ->delete(
                    $rutaNueva
                );


            report($e);


            return response()->json([
                'status' => 'error',

                'message' =>
                    'No fue posible guardar el archivo PDF.',
            ], 500);
        }


        if (
            $rutaAnterior &&
            Storage::disk('public')
                ->exists(
                    $rutaAnterior
                )
        ) {
            Storage::disk('public')
                ->delete(
                    $rutaAnterior
                );
        }


        return response()->json([
            'status' => 'success',

            'message' =>
                'PDF guardado correctamente.',

            'data' =>
                $convocatoria
                    ->fresh('periodo'),
        ], 200);
    }


    /*
    |--------------------------------------------------------------------------
    | ELIMINAR PDF
    |--------------------------------------------------------------------------
    */

    public function eliminarArchivo(
        Convocatoria $convocatoria
    ) {
        $ruta =
            $convocatoria->archivo;


        $convocatoria->update([
            'archivo' =>
                null,
        ]);


        if (
            $ruta &&
            Storage::disk('public')
                ->exists(
                    $ruta
                )
        ) {
            Storage::disk('public')
                ->delete(
                    $ruta
                );
        }


        return response()->json([
            'status' => 'success',

            'message' =>
                'PDF eliminado correctamente.',

            'data' =>
                $convocatoria
                    ->fresh('periodo'),
        ], 200);
    }


    /*
    |--------------------------------------------------------------------------
    | PUBLICAR
    |--------------------------------------------------------------------------
    */

    public function publicar(
        Convocatoria $convocatoria
    ) {
        if (
            strtoupper(
                (string)
                $convocatoria->estado
            ) === 'PUBLICADA'
        ) {
            return response()->json([
                'status' => 'success',

                'message' =>
                    'La convocatoria ya se encuentra publicada.',

                'data' =>
                    $convocatoria
                        ->fresh('periodo'),
            ], 200);
        }


        DB::transaction(
            function () use (
                $convocatoria
            ) {

                Convocatoria::query()
                    ->where(
                        'id',
                        '!=',
                        $convocatoria->id
                    )
                    ->where(
                        'estado',
                        'PUBLICADA'
                    )
                    ->update([
                        'estado' =>
                            'CERRADA',
                    ]);


                $convocatoria->update([
                    'estado' =>
                        'PUBLICADA',
                ]);
            }
        );


        /*
        |--------------------------------------------------------------------------
        | ENVIAR CORREOS A TODOS LOS ALUMNOS
        |--------------------------------------------------------------------------
        */

        try {

            $this->notificarPublicacion(
                $convocatoria
            );

        } catch (\Throwable $e) {

            report($e);


            return response()->json([
                'status' => 'warning',

                'message' =>
                    'La convocatoria fue publicada, pero uno o más correos no pudieron enviarse.',

                'mail_error' =>
                    config('app.debug')
                        ? $e->getMessage()
                        : null,

                'data' =>
                    $convocatoria
                        ->fresh('periodo'),
            ], 200);
        }


        return response()->json([
            'status' => 'success',

            'message' =>
                'Convocatoria publicada y alumnos notificados correctamente.',

            'data' =>
                $convocatoria
                    ->fresh('periodo'),
        ], 200);
    }


    /*
    |--------------------------------------------------------------------------
    | CERRAR
    |--------------------------------------------------------------------------
    */

    public function cerrar(
        Convocatoria $convocatoria
    ) {
        if (
            strtoupper(
                (string)
                $convocatoria->estado
            ) === 'CERRADA'
        ) {
            return response()->json([
                'status' => 'success',

                'message' =>
                    'La convocatoria ya se encuentra cerrada.',

                'data' =>
                    $convocatoria
                        ->fresh('periodo'),
            ], 200);
        }


        $convocatoria->update([
            'estado' =>
                'CERRADA',
        ]);


        /*
        |--------------------------------------------------------------------------
        | ENVIAR CORREO DE CIERRE A TODOS LOS ALUMNOS
        |--------------------------------------------------------------------------
        */

        try {

            $this->notificarCierre(
                $convocatoria
            );

        } catch (\Throwable $e) {

            report($e);


            return response()->json([
                'status' => 'warning',

                'message' =>
                    'La convocatoria fue cerrada, pero uno o más correos no pudieron enviarse.',

                'mail_error' =>
                    config('app.debug')
                        ? $e->getMessage()
                        : null,

                'data' =>
                    $convocatoria
                        ->fresh('periodo'),
            ], 200);
        }


        return response()->json([
            'status' => 'success',

            'message' =>
                'Convocatoria cerrada y alumnos notificados correctamente.',

            'data' =>
                $convocatoria
                    ->fresh('periodo'),
        ], 200);
    }


    /*
    |--------------------------------------------------------------------------
    | ELIMINAR CONVOCATORIA
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Convocatoria $convocatoria
    ) {
        $rutaArchivo =
            $convocatoria->archivo;


        try {

            $convocatoria->delete();

        } catch (\Throwable $e) {

            report($e);


            return response()->json([
                'status' => 'error',

                'message' =>
                    'No fue posible eliminar la convocatoria.',
            ], 422);
        }


        if (
            $rutaArchivo &&
            Storage::disk('public')
                ->exists(
                    $rutaArchivo
                )
        ) {
            Storage::disk('public')
                ->delete(
                    $rutaArchivo
                );
        }


        return response()->json([
            'status' => 'success',

            'message' =>
                'Convocatoria eliminada correctamente.',
        ], 200);
    }


    /*
    |--------------------------------------------------------------------------
    | BUSCAR CONVOCATORIA VIGENTE
    |--------------------------------------------------------------------------
    */

    private function buscarConvocatoriaVigente()
    {
        return Convocatoria::query()
            ->with(
                'periodo'
            )
            ->where(
                'estado',
                'PUBLICADA'
            )
            ->whereDate(
                'fecha_inicio',
                '<=',
                now()->toDateString()
            )
            ->whereDate(
                'fecha_cierre',
                '>=',
                now()->toDateString()
            )
            ->orderByDesc('id')
            ->first();
    }


    /*
    |--------------------------------------------------------------------------
    | OBTENER TODOS LOS ALUMNOS CON CORREO
    |--------------------------------------------------------------------------
    */

    private function alumnosParaNotificacion()
    {
        return User::query()
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
            ->orderBy(
                'id'
            )
            ->get();
    }


    /*
    |--------------------------------------------------------------------------
    | NOTIFICAR PUBLICACIÓN
    |--------------------------------------------------------------------------
    */

    private function notificarPublicacion(
        Convocatoria $convocatoria
    ): void {
        $convocatoria->refresh();


        /*
        |--------------------------------------------------------------------------
        | EVITAR ENVÍO DUPLICADO
        |--------------------------------------------------------------------------
        */

        if (
            $convocatoria
                ->notificacion_publicada_en
        ) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | TODOS LOS ALUMNOS
        |--------------------------------------------------------------------------
        */

        $alumnos =
            $this
                ->alumnosParaNotificacion();


        if (
            $alumnos->isEmpty()
        ) {
            throw new \RuntimeException(
                'No existen alumnos con correo electrónico registrado.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | ENVÍO MASIVO
        |--------------------------------------------------------------------------
        */

        Notification::send(
            $alumnos,
            new ConvocatoriaPublicadaNotification(
                $convocatoria
            )
        );


        /*
        |--------------------------------------------------------------------------
        | MARCAR COMO NOTIFICADA
        |--------------------------------------------------------------------------
        |
        | Solo después de que Notification::send()
        | terminó sin lanzar excepción.
        |
        */

        $convocatoria
            ->forceFill([
                'notificacion_publicada_en'
                    => now(),
            ])
            ->save();
    }


    /*
    |--------------------------------------------------------------------------
    | NOTIFICAR CIERRE
    |--------------------------------------------------------------------------
    */

    private function notificarCierre(
        Convocatoria $convocatoria
    ): void {
        $alumnos =
            $this
                ->alumnosParaNotificacion();


        if (
            $alumnos->isEmpty()
        ) {
            throw new \RuntimeException(
                'No existen alumnos con correo electrónico registrado.'
            );
        }


        Notification::send(
            $alumnos,
            new ConvocatoriaCerradaNotification(
                $convocatoria
            )
        );
    }
}