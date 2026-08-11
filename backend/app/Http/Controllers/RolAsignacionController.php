<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RolAsignacionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LISTAR PERSONAL
    |--------------------------------------------------------------------------
    */

    public function listarStaff()
    {
        $usuarios = User::whereIn(
            'role',
            [
                'admin',
                'profesor',
            ]
        )
            ->with([
                'carrera',
                'carrerasAsignadas',
            ])
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $usuarios,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CREAR PERSONAL
    |--------------------------------------------------------------------------
    */

    public function crearStaff(
        Request $request
    ) {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
            ],

            'role' => [
                'required',
                'in:admin,profesor',
            ],

            'carrera_id' => [
                'nullable',
                'integer',
                'exists:carreras,id',
            ],

            'carreras' => [
                'nullable',
                'array',
            ],

            'carreras.*' => [
                'integer',
                'exists:carreras,id',
            ],
        ]);

        return DB::transaction(
            function () use ($data) {

                $usuario = User::create([
                    'name' =>
                        $data['name'],

                    'email' =>
                        $data['email'],

                    'password' =>
                        Hash::make(
                            $data['password']
                        ),

                    'role' =>
                        $data['role'],

                    'carrera_id' =>
                        $data['carrera_id']
                        ?? null,

                    /*
                    | La primera contraseña
                    | también será temporal.
                    */
                    'must_change_password' =>
                        true,

                    'email_verified_at' =>
                        now(),
                ]);

                $carreras =
                    $data['carreras']
                    ?? [];

                if (
                    empty($carreras) &&
                    !empty(
                        $data['carrera_id']
                    )
                ) {
                    $carreras = [
                        $data['carrera_id'],
                    ];
                }

                if (
                    in_array(
                        $usuario->role,
                        [
                            'admin',
                            'profesor',
                        ],
                        true
                    )
                ) {
                    $usuario
                        ->carrerasAsignadas()
                        ->sync(
                            $carreras
                        );
                }

                return response()->json([
                    'status' =>
                        'success',

                    'message' =>
                        'Personal creado correctamente.',

                    'data' =>
                        $usuario->load([
                            'carrera',
                            'carrerasAsignadas',
                        ]),
                ], 201);
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR PERSONAL
    |--------------------------------------------------------------------------
    */

    public function actualizarStaff(
        Request $request,
        User $usuario
    ) {
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
            return response()->json([
                'status' =>
                    'error',

                'message' =>
                    'El usuario seleccionado no pertenece al personal administrable.',
            ], 422);
        }

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email,' .
                $usuario->id,
            ],

            'role' => [
                'required',
                'in:admin,profesor',
            ],

            'carrera_id' => [
                'nullable',
                'integer',
                'exists:carreras,id',
            ],

            'carreras' => [
                'nullable',
                'array',
            ],

            'carreras.*' => [
                'integer',
                'exists:carreras,id',
            ],
        ]);

        DB::transaction(
            function () use (
                $usuario,
                $data
            ) {
                $usuario->update([
                    'name' =>
                        $data['name'],

                    'email' =>
                        $data['email'],

                    'role' =>
                        $data['role'],

                    'carrera_id' =>
                        $data['carrera_id']
                        ?? null,
                ]);

                $carreras =
                    $data['carreras']
                    ?? [];

                if (
                    empty($carreras) &&
                    !empty(
                        $data['carrera_id']
                    )
                ) {
                    $carreras = [
                        $data['carrera_id'],
                    ];
                }

                $usuario
                    ->carrerasAsignadas()
                    ->sync(
                        $carreras
                    );
            }
        );

        return response()->json([
            'status' => 'success',

            'message' =>
                'Personal actualizado correctamente.',

            'data' =>
                $usuario
                    ->fresh()
                    ->load([
                        'carrera',
                        'carrerasAsignadas',
                    ]),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ELIMINAR PERSONAL
    |--------------------------------------------------------------------------
    */

    public function eliminarStaff(
        Request $request,
        User $usuario
    ) {
        if (
            $usuario->id ===
            $request->user()->id
        ) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'No puedes eliminar tu propia cuenta.',
            ], 422);
        }

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
            return response()->json([
                'status' => 'error',
                'message' =>
                    'Solo se puede eliminar personal administrativo o profesor.',
            ], 422);
        }

        DB::transaction(
            function () use ($usuario) {

                $usuario
                    ->carrerasAsignadas()
                    ->detach();

                /*
                | Si es tutor de grupos,
                | dejamos esos grupos sin tutor.
                */
                $usuario
                    ->gruposTutor()
                    ->update([
                        'tutor_id' =>
                            null,
                    ]);

                $usuario
                    ->tokens()
                    ->delete();

                $usuario->delete();
            }
        );

        return response()->json([
            'status' =>
                'success',

            'message' =>
                'Personal eliminado correctamente.',
        ]);
    }
}