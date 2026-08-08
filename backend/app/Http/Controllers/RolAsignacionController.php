<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class RolAsignacionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LISTAR PERSONAL
    |--------------------------------------------------------------------------
    |
    | Devuelve únicamente personal administrativo:
    | admin / profesor
    |
    */

    public function listarStaff(Request $request)
    {
        try {

            $query = User::query()
                ->whereIn('role', [
                    'admin',
                    'profesor',
                ])
                ->orderBy('name');


            /*
            |--------------------------------------------------------------------------
            | Cargamos carrera solamente si la relación existe correctamente.
            |--------------------------------------------------------------------------
            */

            try {
                $query->with('carrera');
            } catch (\Throwable $e) {
                // La consulta continuará sin la relación.
            }


            $usuarios = $query->get();


            /*
            |--------------------------------------------------------------------------
            | Formato seguro para Vue.
            |--------------------------------------------------------------------------
            */

            $data = $usuarios->map(function ($usuario) {

                return [
                    'id' => $usuario->id,

                    'name' => $usuario->name,

                    'email' => $usuario->email,

                    'role' => $usuario->role,

                    'carrera_id' =>
                        $usuario->carrera_id,

                    'grupo' =>
                        $usuario->grupo,

                    'grupo_id' =>
                        $usuario->grupo_id ?? null,

                    'carrera' =>
                        $usuario->relationLoaded('carrera')
                            ? $usuario->carrera
                            : null,

                    'created_at' =>
                        $usuario->created_at,

                    'updated_at' =>
                        $usuario->updated_at,
                ];
            });


            return response()->json([
                'status' => 'success',

                'data' => $data,
            ]);

        } catch (\Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Esto te permitirá ver el error real si algo todavía falla.
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'status' => 'error',

                'message' =>
                    'Error al cargar el personal.',

                'error' =>
                    $e->getMessage(),
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CREAR PERSONAL
    |--------------------------------------------------------------------------
    */

    public function crearStaff(Request $request)
    {
        try {

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

                    Rule::in([
                        'admin',
                        'profesor',
                    ]),
                ],

                /*
                |--------------------------------------------------------------------------
                | Tu dashboard manda "carreras": [id]
                |--------------------------------------------------------------------------
                */

                'carreras' => [
                    'nullable',
                    'array',
                ],

                'carreras.*' => [
                    'integer',
                    'exists:carreras,id',
                ],
            ]);


            DB::beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | Por ahora tomamos la primera carrera.
            |
            | Esto conserva compatibilidad con tu users.carrera_id.
            |--------------------------------------------------------------------------
            */

            $carreraId = null;

            if (
                !empty($data['carreras']) &&
                isset($data['carreras'][0])
            ) {
                $carreraId =
                    $data['carreras'][0];
            }


            $usuario = User::create([
                'name' =>
                    $data['name'],

                'email' =>
                    strtolower(
                        trim($data['email'])
                    ),

                'password' =>
                    Hash::make(
                        $data['password']
                    ),

                'role' =>
                    $data['role'],

                'carrera_id' =>
                    $carreraId,

                'email_verified_at' =>
                    now(),
            ]);


            DB::commit();


            try {
                $usuario->load('carrera');
            } catch (\Throwable $e) {
                // No interrumpe la creación.
            }


            return response()->json([
                'status' => 'success',

                'message' =>
                    'Usuario institucional creado correctamente.',

                'data' =>
                    $usuario,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {

            if (
                DB::transactionLevel() > 0
            ) {
                DB::rollBack();
            }

            throw $e;

        } catch (\Throwable $e) {

            if (
                DB::transactionLevel() > 0
            ) {
                DB::rollBack();
            }


            return response()->json([
                'status' => 'error',

                'message' =>
                    'No se pudo crear el usuario.',

                'error' =>
                    $e->getMessage(),
            ], 500);
        }
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
        try {

            /*
            |--------------------------------------------------------------------------
            | Evitamos borrar alumnos desde este endpoint.
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

                return response()->json([
                    'status' => 'error',

                    'message' =>
                        'Este usuario no pertenece al personal administrativo.',
                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | Un SuperAdmin no debería borrarse aquí accidentalmente.
            |--------------------------------------------------------------------------
            */

            if (
                $usuario->id ===
                $request->user()?->id
            ) {

                return response()->json([
                    'status' => 'error',

                    'message' =>
                        'No puedes eliminar tu propio usuario desde esta sección.',
                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | Revocar tokens si usa Sanctum.
            |--------------------------------------------------------------------------
            */

            try {
                $usuario
                    ->tokens()
                    ->delete();
            } catch (\Throwable $e) {
                // Continuamos.
            }


            $nombre =
                $usuario->name;


            $usuario->delete();


            return response()->json([
                'status' => 'success',

                'message' =>
                    "Usuario {$nombre} eliminado correctamente.",
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => 'error',

                'message' =>
                    'No se pudo eliminar el usuario.',

                'error' =>
                    $e->getMessage(),
            ], 500);
        }
    }
}