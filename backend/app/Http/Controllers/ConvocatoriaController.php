<?php

namespace App\Http\Controllers;

use App\Models\Convocatoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConvocatoriaController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LISTADO ADMIN / MASTER
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $convocatorias = Convocatoria::with('periodo')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $convocatorias
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | CONVOCATORIAS PÚBLICAS
    |--------------------------------------------------------------------------
    */

    public function publica()
    {
        $convocatorias = Convocatoria::with('periodo')
            ->where('estado', 'PUBLICADA')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'status' => 'success',
            'convocatorias' => $convocatorias
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | CONVOCATORIA VIGENTE
    |--------------------------------------------------------------------------
    */

    public function obtenerVigente()
    {
        $convocatoria = Convocatoria::with('periodo')
            ->where('estado', 'PUBLICADA')
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => $convocatoria
        ], 200);
    }

    public function getActiva()
    {
        return $this->obtenerVigente();
    }

    /*
    |--------------------------------------------------------------------------
    | CONVOCATORIA PARA ALUMNO
    |--------------------------------------------------------------------------
    */

    public function actual()
    {
        $convocatoria = Convocatoria::with('periodo')
            ->where('estado', 'PUBLICADA')
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => $convocatoria
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | CREAR
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $validated = $request->validate([
            'periodo_id' => 'required|exists:periodos,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'requisitos' => 'required|string',
            'promedio_minimo' => 'required|numeric|min:0|max:10',
            'fecha_inicio' => 'required|date',
            'fecha_cierre' => 'required|date|after_or_equal:fecha_inicio',
            'estado' => 'required|in:BORRADOR,PUBLICADA,CERRADA',

            'archivo' => [
                'nullable',
                'file',
                'mimes:pdf',
                'max:10240',
            ],
        ]);

        if ($validated['estado'] === 'PUBLICADA') {
            Convocatoria::where(
                'periodo_id',
                $validated['periodo_id']
            )
            ->where('estado', 'PUBLICADA')
            ->update([
                'estado' => 'CERRADA'
            ]);
        }

        if ($request->hasFile('archivo')) {
            $validated['archivo'] = $request
                ->file('archivo')
                ->store('convocatorias', 'public');
        }

        $convocatoria = Convocatoria::create($validated);

        $convocatoria->load('periodo');

        return response()->json([
            'status' => 'success',
            'message' => 'Convocatoria creada correctamente.',
            'data' => $convocatoria
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR DATOS
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, Convocatoria $convocatoria)
    {
        $validated = $request->validate([
            'periodo_id' => 'sometimes|required|exists:periodos,id',
            'nombre' => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string',
            'requisitos' => 'sometimes|required|string',
            'promedio_minimo' => 'sometimes|required|numeric|min:0|max:10',
            'fecha_inicio' => 'sometimes|required|date',
            'fecha_cierre' => 'sometimes|required|date',
            'estado' => 'sometimes|required|in:BORRADOR,PUBLICADA,CERRADA',
        ]);

        $convocatoria->update($validated);
        $convocatoria->load('periodo');

        return response()->json([
            'status' => 'success',
            'message' => 'Convocatoria actualizada correctamente.',
            'data' => $convocatoria
        ]);
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

        if (
            $convocatoria->archivo &&
            Storage::disk('public')->exists($convocatoria->archivo)
        ) {
            Storage::disk('public')->delete($convocatoria->archivo);
        }

        $ruta = $request
            ->file('archivo')
            ->store('convocatorias', 'public');

        $convocatoria->update([
            'archivo' => $ruta
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'PDF reemplazado correctamente.',
            'data' => $convocatoria->fresh('periodo')
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ELIMINAR SOLAMENTE PDF
    |--------------------------------------------------------------------------
    */

    public function eliminarArchivo(Convocatoria $convocatoria)
    {
        if (
            $convocatoria->archivo &&
            Storage::disk('public')->exists($convocatoria->archivo)
        ) {
            Storage::disk('public')->delete($convocatoria->archivo);
        }

        $convocatoria->update([
            'archivo' => null
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'PDF eliminado correctamente.'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | PUBLICAR
    |--------------------------------------------------------------------------
    */

    public function publicar(Convocatoria $convocatoria)
    {
        Convocatoria::where(
            'periodo_id',
            $convocatoria->periodo_id
        )
        ->where('id', '!=', $convocatoria->id)
        ->where('estado', 'PUBLICADA')
        ->update([
            'estado' => 'CERRADA'
        ]);

        $convocatoria->update([
            'estado' => 'PUBLICADA'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Convocatoria publicada.',
            'data' => $convocatoria->fresh('periodo')
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CERRAR
    |--------------------------------------------------------------------------
    */

    public function cerrar(Convocatoria $convocatoria)
    {
        $convocatoria->update([
            'estado' => 'CERRADA'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Convocatoria cerrada.',
            'data' => $convocatoria->fresh('periodo')
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ELIMINAR CONVOCATORIA
    |--------------------------------------------------------------------------
    */

    public function destroy(Convocatoria $convocatoria)
    {
        if (
            $convocatoria->archivo &&
            Storage::disk('public')->exists($convocatoria->archivo)
        ) {
            Storage::disk('public')->delete($convocatoria->archivo);
        }

        $convocatoria->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Convocatoria eliminada.'
        ]);
    }
}