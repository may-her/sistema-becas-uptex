<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CarreraController extends Controller
{
    public function index()
    {
        $carreras = Carrera::query()
            ->withCount([
                'alumnos',
                'grupos'
            ])
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $carreras
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:150',
                'unique:carreras,nombre'
            ],

            'clave' => [
                'nullable',
                'string',
                'max:20',
                'unique:carreras,clave'
            ],

            'descripcion' => [
                'nullable',
                'string'
            ],

            'estado' => [
                'required',
                Rule::in([
                    'ACTIVA',
                    'INACTIVA'
                ])
            ]
        ]);

        $carrera = Carrera::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Carrera creada correctamente.',
            'data' => $carrera
        ], 201);
    }

    public function show(Carrera $carrera)
    {
        $carrera->load([
            'grupos.periodo',
            'grupos.tutor'
        ]);

        $carrera->loadCount([
            'alumnos',
            'grupos'
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $carrera
        ]);
    }

    public function update(
        Request $request,
        Carrera $carrera
    ) {
        $data = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:150',
                Rule::unique(
                    'carreras',
                    'nombre'
                )->ignore($carrera->id)
            ],

            'clave' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique(
                    'carreras',
                    'clave'
                )->ignore($carrera->id)
            ],

            'descripcion' => [
                'nullable',
                'string'
            ],

            'estado' => [
                'required',
                Rule::in([
                    'ACTIVA',
                    'INACTIVA'
                ])
            ]
        ]);

        $carrera->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Carrera actualizada.',
            'data' => $carrera->fresh()
        ]);
    }

    public function destroy(Carrera $carrera)
    {
        if (
            $carrera->alumnos()->exists() ||
            $carrera->grupos()->exists()
        ) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'No puedes eliminar una carrera que tiene alumnos o grupos relacionados. Puedes marcarla como INACTIVA.'
            ], 422);
        }

        $carrera->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Carrera eliminada.'
        ]);
    }
}
