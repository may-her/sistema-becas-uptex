<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Models\Solicitud;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DocumentoController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | SUBIR DOCUMENTO
    |--------------------------------------------------------------------------
    |
    | Utilizado por el alumno.
    |
    | POST:
    | /api/alumno/solicitudes/{solicitud}/documentos
    |
    */

    public function upload(
        Request $request,
        Solicitud $solicitud
    ) {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json([
                'message' => 'Usuario no autenticado.',
            ], 401);
        }


        /*
        |--------------------------------------------------------------------------
        | EL ALUMNO SOLO PUEDE SUBIR A SU PROPIA SOLICITUD
        |--------------------------------------------------------------------------
        */

        if (
            $usuario->role === 'alumno' &&
            (int) $solicitud->user_id !== (int) $usuario->id
        ) {
            return response()->json([
                'message' =>
                    'No tienes permiso para modificar esta solicitud.',
            ], 403);
        }


        /*
        |--------------------------------------------------------------------------
        | VALIDACIÓN
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'tipo_documento' => [
                'required',
                'string',
                'max:120',
            ],

            'archivo' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:10240',
            ],
        ]);


        $archivo = $request->file('archivo');


        /*
        |--------------------------------------------------------------------------
        | GUARDAR ARCHIVO
        |--------------------------------------------------------------------------
        */

        $ruta = $archivo->store(
            'solicitudes/' . $solicitud->id,
            'public'
        );


        try {

            $documento = DB::transaction(
                function () use (
                    $solicitud,
                    $validated,
                    $archivo,
                    $ruta
                ) {

                    return Documento::create([
                        'solicitud_id' =>
                            $solicitud->id,

                        'tipo_documento' =>
                            $validated['tipo_documento'],

                        'ruta_archivo' =>
                            $ruta,

                        'nombre_original' =>
                            $archivo->getClientOriginalName(),

                        'estado' =>
                            'PENDIENTE',

                        'observaciones' =>
                            null,

                        'revisado_por' =>
                            null,

                        'revisado_at' =>
                            null,
                    ]);
                }
            );

        } catch (\Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | SI FALLA LA BD BORRAMOS EL ARCHIVO
            |--------------------------------------------------------------------------
            */

            Storage::disk('public')
                ->delete($ruta);

            report($e);

            return response()->json([
                'message' =>
                    'El archivo se recibió, pero no pudo registrarse en la base de datos.',

                'error' =>
                    config('app.debug')
                        ? $e->getMessage()
                        : null,
            ], 500);
        }


        return response()->json([
            'message' =>
                'Documento cargado correctamente.',

            'data' =>
                $documento->fresh(),
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | SOLICITAR CORRECCIÓN
    |--------------------------------------------------------------------------
    |
    | Profesor/Admin/SuperAdmin puede marcar un documento
    | para que el alumno vuelva a subirlo.
    |
    */

    public function solicitarCorreccion(
        Request $request,
        Documento $documento
    ) {
        $validated = $request->validate([
            'observaciones' => [
                'required',
                'string',
                'max:1000',
            ],
        ]);


        if (!$this->puedeRevisarDocumento(
            $request,
            $documento
        )) {
            return response()->json([
                'message' =>
                    'No tienes permiso para revisar este documento.',
            ], 403);
        }


        $documento->update([
            'estado' =>
                'RECHAZADO',

            'observaciones' =>
                $validated['observaciones'],

            'revisado_por' =>
                $request->user()->id,

            'revisado_at' =>
                now(),
        ]);


        /*
        |--------------------------------------------------------------------------
        | CAMBIAR SOLICITUD A DOCUMENTACIÓN INCOMPLETA
        |--------------------------------------------------------------------------
        */

        if ($documento->solicitud) {
            $documento
                ->solicitud
                ->update([
                    'estado' =>
                        'DOCUMENTACION_INCOMPLETA',
                ]);
        }


        return response()->json([
            'message' =>
                'Se solicitó una corrección al alumno.',

            'data' =>
                $documento->fresh(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAR DOCUMENTO
    |--------------------------------------------------------------------------
    */

    public function validar(
        Request $request,
        Documento $documento
    ) {
        if (!$this->puedeRevisarDocumento(
            $request,
            $documento
        )) {
            return response()->json([
                'message' =>
                    'No tienes permiso para revisar este documento.',
            ], 403);
        }


        $documento->update([
            'estado' =>
                'VALIDO',

            'observaciones' =>
                null,

            'revisado_por' =>
                $request->user()->id,

            'revisado_at' =>
                now(),
        ]);


        return response()->json([
            'message' =>
                'Documento validado correctamente.',

            'data' =>
                $documento->fresh(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | RECHAZAR DOCUMENTO
    |--------------------------------------------------------------------------
    */

    public function rechazar(
        Request $request,
        Documento $documento
    ) {
        $validated = $request->validate([
            'observaciones' => [
                'required',
                'string',
                'max:1000',
            ],
        ]);


        if (!$this->puedeRevisarDocumento(
            $request,
            $documento
        )) {
            return response()->json([
                'message' =>
                    'No tienes permiso para revisar este documento.',
            ], 403);
        }


        $documento->update([
            'estado' =>
                'RECHAZADO',

            'observaciones' =>
                $validated['observaciones'],

            'revisado_por' =>
                $request->user()->id,

            'revisado_at' =>
                now(),
        ]);


        if ($documento->solicitud) {
            $documento
                ->solicitud
                ->update([
                    'estado' =>
                        'DOCUMENTACION_INCOMPLETA',
                ]);
        }


        return response()->json([
            'message' =>
                'Documento rechazado.',

            'data' =>
                $documento->fresh(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | PERMISOS DE REVISIÓN
    |--------------------------------------------------------------------------
    */

    private function puedeRevisarDocumento(
        Request $request,
        Documento $documento
    ): bool {
        $usuario = $request->user();

        if (!$usuario) {
            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | SUPERADMIN
        |--------------------------------------------------------------------------
        */

        if (
            $usuario->role ===
            'superadmin'
        ) {
            return true;
        }


        /*
        |--------------------------------------------------------------------------
        | ADMIN / PROFESOR
        |--------------------------------------------------------------------------
        */

        if (
            !in_array(
                $usuario->role,
                [
                    'admin',
                    'profesor',
                ],
                true
            )
        ) {
            return false;
        }


        $documento->loadMissing(
            'solicitud'
        );


        if (!$documento->solicitud) {
            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | CARRERAS ASIGNADAS AL USUARIO
        |--------------------------------------------------------------------------
        */

        $carreras = DB::table(
            'asignaciones_carrera'
        )
            ->where(
                'user_id',
                $usuario->id
            )
            ->pluck(
                'carrera_id'
            )
            ->map(
                fn ($id) => (int) $id
            );


        return $carreras->contains(
            (int)
            $documento
                ->solicitud
                ->carrera_id
        );
    }
}