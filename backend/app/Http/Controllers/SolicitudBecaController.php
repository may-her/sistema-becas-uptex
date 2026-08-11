<?php

namespace App\Http\Controllers;

use App\Models\Solicitud;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SolicitudBecaController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | TODAS LAS SOLICITUDES - SUPERADMIN
    |--------------------------------------------------------------------------
    */

    public function todas(
        Request $request
    ) {
        $solicitudes =
            Solicitud::query()
                ->with([
                    'usuario',
                    'convocatoria',
                    'carrera',
                    'grupoRelacion',
                    'documentos',
                ])
                ->orderByDesc('id')
                ->get();


        return response()->json([
            'data' =>
                $solicitudes,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | SOLICITUDES POR CARRERA ASIGNADA
    |--------------------------------------------------------------------------
    |
    | Para:
    |
    | admin
    | profesor
    |
    */

    public function porCarreraAsignada(
        Request $request
    ) {
        $usuario =
            $request->user();


        if (!$usuario) {
            return response()->json([
                'message' =>
                    'Usuario no autenticado.',
            ], 401);
        }


        /*
        |--------------------------------------------------------------------------
        | OBTENER CARRERAS ASIGNADAS
        |--------------------------------------------------------------------------
        */

        $carreras =
            DB::table(
                'asignaciones_carrera'
            )
            ->where(
                'user_id',
                $usuario->id
            )
            ->pluck(
                'carrera_id'
            );


        if ($carreras->isEmpty()) {

            return response()->json([
                'data' => [],
                'message' =>
                    'No tienes carreras asignadas.',
            ]);
        }


        $solicitudes =
            Solicitud::query()
                ->with([
                    'usuario',
                    'convocatoria',
                    'carrera',
                    'grupoRelacion',
                    'documentos',
                ])
                ->whereIn(
                    'carrera_id',
                    $carreras
                )
                ->orderByDesc('id')
                ->get();


        return response()->json([
            'data' =>
                $solicitudes,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | VER EXPEDIENTE
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        Solicitud $solicitud
    ) {
        if (
            !$this->puedeRevisar(
                $request,
                $solicitud
            )
        ) {
            return response()->json([
                'message' =>
                    'No tienes permiso para consultar este expediente.',
            ], 403);
        }


        $solicitud->load([
            'usuario',
            'convocatoria',
            'carrera',
            'grupoRelacion',
            'documentos',
            'documentos.revisor',
            'revisor',
        ]);


        return response()->json([
            'data' =>
                $solicitud,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR ESTADO
    |--------------------------------------------------------------------------
    */

    public function actualizarEstatus(
        Request $request,
        Solicitud $solicitud
    ) {
        if (
            !$this->puedeRevisar(
                $request,
                $solicitud
            )
        ) {
            return response()->json([
                'message' =>
                    'No tienes permiso para modificar esta solicitud.',
            ], 403);
        }


        $validated =
            $request->validate([
                'estado' => [
                    'required',
                    'string',
                    'in:PENDIENTE,EN_REVISION,DOCUMENTACION_INCOMPLETA,ACEPTADA,RECHAZADA',
                ],
            ]);


        $solicitud->update([
            'estado' =>
                $validated['estado'],

            'revisado_por' =>
                $request->user()->id,

            'fecha_revision' =>
                now(),
        ]);


        return response()->json([
            'message' =>
                'Estado actualizado correctamente.',

            'data' =>
                $solicitud->fresh(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | DICTAMINAR
    |--------------------------------------------------------------------------
    |
    | Aquí diferenciamos:
    |
    | porcentaje_solicitado = lo que pidió el alumno
    | porcentaje_beca       = lo que se autorizó
    |
    */

    public function dictaminar(
        Request $request,
        Solicitud $solicitud
    ) {
        if (
            !$this->puedeRevisar(
                $request,
                $solicitud
            )
        ) {
            return response()->json([
                'message' =>
                    'No tienes permiso para dictaminar esta solicitud.',
            ], 403);
        }


        $validated =
            $request->validate([
                'estado' => [
                    'required',
                    'string',
                    'in:ACEPTADA,RECHAZADA',
                ],

                'porcentaje_beca' => [
                    'nullable',
                    'numeric',
                    'between:0,100',
                ],

                'comentario_revision' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],
            ]);


        /*
        |--------------------------------------------------------------------------
        | SI ES ACEPTADA, EL PORCENTAJE ES OBLIGATORIO
        |--------------------------------------------------------------------------
        */

        if (
            $validated['estado'] ===
            'ACEPTADA'
            &&
            (
                !array_key_exists(
                    'porcentaje_beca',
                    $validated
                )
                ||
                $validated[
                    'porcentaje_beca'
                ] === null
            )
        ) {
            return response()->json([
                'message' =>
                    'Debes indicar el porcentaje de beca autorizado.',
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | SI ES RECHAZADA NO GUARDAMOS PORCENTAJE
        |--------------------------------------------------------------------------
        */

        $porcentaje =
            $validated['estado'] ===
            'ACEPTADA'
                ? $validated[
                    'porcentaje_beca'
                ]
                : null;


        $solicitud->update([
            'estado' =>
                $validated['estado'],

            'porcentaje_beca' =>
                $porcentaje,

            'comentario_revision' =>
                $validated[
                    'comentario_revision'
                ]
                ?? null,

            'revisado_por' =>
                $request->user()->id,

            'fecha_revision' =>
                now(),
        ]);


        $solicitud->load([
            'usuario',
            'convocatoria',
            'carrera',
            'grupoRelacion',
            'documentos',
        ]);


        return response()->json([
            'message' =>
                $validated['estado'] ===
                'ACEPTADA'
                    ? 'Solicitud aceptada correctamente.'
                    : 'Solicitud rechazada.',

            'data' =>
                $solicitud,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | VERIFICAR PERMISO
    |--------------------------------------------------------------------------
    */

    private function puedeRevisar(
        Request $request,
        Solicitud $solicitud
    ): bool {
        $usuario =
            $request->user();


        if (!$usuario) {
            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | SUPERADMIN PUEDE VER TODO
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


        $carreras =
            DB::table(
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
                fn ($id) =>
                    (int) $id
            );


        return $carreras->contains(
            (int)
            $solicitud->carrera_id
        );
    }
}