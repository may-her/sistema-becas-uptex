<?php

namespace App\Http\Controllers;

use App\Models\Grupo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GrupoController extends Controller
{
    public function index(Request $request)
    {
        $query = Grupo::query()
            ->with([
                'carrera:id,nombre',
                'periodo:id,nombre',
                'tutor:id,name,email'
            ])
            ->withCount('alumnos');

        if ($request->filled('carrera_id')) {
            $query->where(
                'carrera_id',
                $request->carrera_id
            );
        }

        if ($request->filled('periodo_id')) {
            $query->where(
                'periodo_id',
                $request->periodo_id
            );
        }

        if ($request->filled('estado')) {
            $query->where(
                'estado',
                $request->estado
            );
        }

        return response()->json([
            'status' => 'success',
            'data' => $query
                ->orderBy('nombre')
                ->get()
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validar($request);

        $grupo = Grupo::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Grupo creado correctamente.',
            'data' => $grupo->load([
                'carrera',
                'periodo',
                'tutor'
            ])
        ], 201);
    }

    public function show(Grupo $grupo)
    {
        $grupo->load([
            'carrera',
            'periodo',
            'tutor',
            'alumnos' => function ($query) {
                $query
                    ->select([
                        'id',
                        'name',
                        'email',
                        'matricula',
                        'carrera_id',
                        'grupo_id',
                        'grupo'
                    ])
                    ->orderBy('name');
            }
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $grupo
        ]);
    }

    public function update(
        Request $request,
        Grupo $grupo
    ) {
        $data = $this->validar(
            $request,
            $grupo
        );

        $grupo->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Grupo actualizado.',
            'data' => $grupo
                ->fresh()
                ->load([
                    'carrera',
                    'periodo',
                    'tutor'
                ])
        ]);
    }

    public function destroy(Grupo $grupo)
    {
        if ($grupo->alumnos()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'El grupo tiene alumnos asignados. Muévelos a otro grupo antes de eliminarlo.'
            ], 422);
        }

        $grupo->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Grupo eliminado.'
        ]);
    }

    public function asignarAlumno(
        Request $request,
        Grupo $grupo
    ) {
        $data = $request->validate([
            'alumno_id' => [
                'required',
                'integer',
                'exists:users,id'
            ]
        ]);

        $alumno = User::findOrFail(
            $data['alumno_id']
        );

        if ($alumno->role !== 'alumno') {
            return response()->json([
                'message' =>
                    'El usuario seleccionado no es alumno.'
            ], 422);
        }

        $alumno->carrera_id = $grupo->carrera_id;
        $alumno->grupo_id = $grupo->id;

        /*
        |--------------------------------------------------------------------------
        | Compatibilidad temporal con el campo grupo antiguo.
        |--------------------------------------------------------------------------
        */

        $alumno->grupo = $grupo->nombre;
        $alumno->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Alumno asignado al grupo.',
            'data' => $alumno->load([
                'carrera',
                'grupoRelacion'
            ])
        ]);
    }

    public function quitarAlumno(
        Grupo $grupo,
        User $alumno
    ) {
        if (
            (int) $alumno->grupo_id !==
            (int) $grupo->id
        ) {
            return response()->json([
                'message' =>
                    'El alumno no pertenece a este grupo.'
            ], 422);
        }

        $alumno->grupo_id = null;
        $alumno->grupo = null;
        $alumno->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Alumno retirado del grupo.'
        ]);
    }

    private function validar(
        Request $request,
        ?Grupo $grupo = null
    ) {
        return $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:30'
            ],

            'carrera_id' => [
                'required',
                'exists:carreras,id'
            ],

            'periodo_id' => [
                'nullable',
                'exists:periodos,id'
            ],

            'tutor_id' => [
                'nullable',
                'exists:users,id'
            ],

            'cuatrimestre' => [
                'nullable',
                'integer',
                'min:1',
                'max:12'
            ],

            'turno' => [
                'required',
                Rule::in([
                    'MATUTINO',
                    'VESPERTINO',
                    'MIXTO'
                ])
            ],

            'estado' => [
                'required',
                Rule::in([
                    'ACTIVO',
                    'INACTIVO'
                ])
            ]
        ]);
    }
}
