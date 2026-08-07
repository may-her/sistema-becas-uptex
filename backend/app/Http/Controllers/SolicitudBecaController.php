<?php

namespace App\Http\Controllers;

use App\Models\Solicitud;
use App\Models\Convocatoria;
use Illuminate\Http\Request;

class SolicitudBecaController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | TODAS LAS SOLICITUDES
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $solicitudes = Solicitud::with([
            'user',
            'convocatoria.periodo',
            'documentos'
        ])
        ->orderBy('id', 'desc')
        ->get();

        return response()->json([
            'status' => 'success',
            'data' => $solicitudes
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | MASTER - TODAS
    |--------------------------------------------------------------------------
    */

    public function todas()
    {
        return $this->index();
    }


    /*
    |--------------------------------------------------------------------------
    | ALUMNO - MIS SOLICITUDES
    |--------------------------------------------------------------------------
    */

    public function misSolicitudes(Request $request)
    {
        $solicitudes = Solicitud::with([
            'convocatoria.periodo',
            'documentos'
        ])
        ->where('user_id', $request->user()->id)
        ->orderBy('id', 'desc')
        ->get();

        return response()->json([
            'status' => 'success',
            'data' => $solicitudes
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | ALUMNO - SOLICITUD ACTIVA
    |--------------------------------------------------------------------------
    */

    public function miSolicitudActiva(Request $request)
    {
        $solicitud = Solicitud::with([
            'convocatoria.periodo',
            'documentos'
        ])
        ->where('user_id', $request->user()->id)
        ->whereIn('estado', [
            'PENDIENTE',
            'EN_REVISION',
            'DOCUMENTACION_INCOMPLETA'
        ])
        ->orderBy('id', 'desc')
        ->first();

        return response()->json([
            'status' => 'success',
            'data' => $solicitud
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | ALUMNO - SOLICITAR BECA
    |--------------------------------------------------------------------------
    */

    public function crear(Request $request)
    {
        $validated = $request->validate([
            'convocatoria_id' => [
                'required',
                'exists:convocatorias,id'
            ]
        ]);

        $convocatoria = Convocatoria::findOrFail(
            $validated['convocatoria_id']
        );

        /*
        |--------------------------------------------------------------------------
        | Validar que la convocatoria esté publicada
        |--------------------------------------------------------------------------
        */

        if ($convocatoria->estado !== 'PUBLICADA') {
            return response()->json([
                'status' => 'error',
                'message' => 'La convocatoria no está disponible para recibir solicitudes.'
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Evitar solicitudes duplicadas
        |--------------------------------------------------------------------------
        */

        $existente = Solicitud::where('user_id', $request->user()->id)
            ->where('convocatoria_id', $convocatoria->id)
            ->first();

        if ($existente) {

            $existente->load([
                'convocatoria.periodo',
                'documentos'
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Ya tienes una solicitud registrada para esta convocatoria.',
                'data' => $existente
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Crear solicitud
        |--------------------------------------------------------------------------
        */

        $solicitud = Solicitud::create([
            'user_id' => $request->user()->id,
            'convocatoria_id' => $convocatoria->id,
            'estado' => 'PENDIENTE'
        ]);

        $solicitud->load([
            'convocatoria.periodo',
            'documentos'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Solicitud registrada correctamente.',
            'data' => $solicitud
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | COMPATIBILIDAD CON store()
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        return $this->crear($request);
    }


    /*
    |--------------------------------------------------------------------------
    | ALUMNO - SUBIR DOCUMENTO
    |--------------------------------------------------------------------------
    */

    public function subirDocumento(
        Request $request,
        $solicitud
    ) {
        /*
        |--------------------------------------------------------------------------
        | Por ahora validamos que la solicitud pertenezca al alumno.
        |--------------------------------------------------------------------------
        */

        $registro = Solicitud::where('id', $solicitud)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$registro) {
            return response()->json([
                'status' => 'error',
                'message' => 'La solicitud no existe o no pertenece al alumno.'
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Esta función necesita conocer exactamente la estructura
        | de tu tabla documentos antes de guardar el archivo.
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'status' => 'error',
            'message' => 'La solicitud fue encontrada correctamente. La carga de documentos requiere conectar el modelo Documento con sus campos reales.'
        ], 501);
    }


    /*
    |--------------------------------------------------------------------------
    | JEFE / TUTOR - SOLICITUDES DE SU CARRERA
    |--------------------------------------------------------------------------
    */

    public function porCarreraAsignada(Request $request)
    {
        $usuario = $request->user();

        $query = Solicitud::with([
            'user',
            'convocatoria.periodo',
            'documentos'
        ]);

        /*
        |--------------------------------------------------------------------------
        | Si el usuario tiene carrera_id, filtramos por ella.
        |--------------------------------------------------------------------------
        */

        if ($usuario->carrera_id) {

            $query->whereHas('user', function ($q) use ($usuario) {
                $q->where('carrera_id', $usuario->carrera_id);
            });
        }

        $solicitudes = $query
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $solicitudes
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR ESTATUS
    |--------------------------------------------------------------------------
    */

    public function actualizarEstatus(
        Request $request,
        $solicitud
    ) {
        $validated = $request->validate([
            'estado' => [
                'required',
                'in:PENDIENTE,EN_REVISION,ACEPTADA,RECHAZADA,DOCUMENTACION_INCOMPLETA'
            ]
        ]);

        $registro = Solicitud::findOrFail($solicitud);

        $registro->update([
            'estado' => $validated['estado']
        ]);

        $registro->load([
            'user',
            'convocatoria.periodo',
            'documentos'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'El estado de la solicitud fue actualizado correctamente.',
            'data' => $registro
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | DICTAMINAR
    |--------------------------------------------------------------------------
    */

    public function dictaminar(
        Request $request,
        $id
    ) {
        $validated = $request->validate([
            'estado' => [
                'required',
                'in:ACEPTADA,RECHAZADA,DOCUMENTACION_INCOMPLETA,EN_REVISION'
            ],

            'observaciones' => [
                'nullable',
                'string'
            ]
        ]);

        $solicitud = Solicitud::findOrFail($id);

        $solicitud->update([
            'estado' => $validated['estado'],
            'observaciones' => $validated['observaciones'] ?? null
        ]);

        $solicitud->load([
            'user',
            'convocatoria.periodo',
            'documentos'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Dictamen guardado correctamente.',
            'data' => $solicitud
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | MI SOLICITUD - COMPATIBILIDAD
    |--------------------------------------------------------------------------
    */

    public function miSolicitud(Request $request)
    {
        return $this->miSolicitudActiva($request);
    }
}