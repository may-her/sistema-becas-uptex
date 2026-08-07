<?php

namespace App\Http\Controllers;

use App\Models\Periodo;
use Illuminate\Http\Request;

class PeriodoController extends Controller
{
    public function index()
    {
        $periodos = Periodo::withCount('convocatorias')->orderBy('id', 'desc')->get();
        return response()->json([
            'status' => 'success',
            'data' => $periodos
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'estado' => 'required|in:ACTIVO,CERRADO'
        ]);

        if ($validated['estado'] === 'ACTIVO') {
            Periodo::where('estado', 'ACTIVO')->update(['estado' => 'CERRADO']);
        }

        $periodo = Periodo::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Periodo creado correctamente',
            'data' => $periodo
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $periodo = Periodo::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'estado' => 'required|in:ACTIVO,CERRADO'
        ]);

        if ($validated['estado'] === 'ACTIVO' && $periodo->estado !== 'ACTIVO') {
            Periodo::where('estado', 'ACTIVO')->update(['estado' => 'CERRADO']);
        }

        $periodo->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Periodo actualizado correctamente',
            'data' => $periodo
        ], 200);
    }

    public function destroy($id)
    {
        $periodo = Periodo::findOrFail($id);

        if ($periodo->convocatorias()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se puede eliminar un periodo que contiene convocatorias registradas.'
            ], 422);
        }

        $periodo->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Periodo eliminado correctamente'
        ], 200);
    }
}