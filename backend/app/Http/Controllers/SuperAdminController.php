<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Solicitud;
use App\Models\Convocatoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SuperAdminController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | ESTADÍSTICAS
    |--------------------------------------------------------------------------
    */

    public function getStats()
    {
        return response()->json([
            'status' => 'success',

            'stats' => [
                'solicitudes' =>
                    Solicitud::count(),

                'pendientes' =>
                    Solicitud::where(
                        'estado',
                        'PENDIENTE'
                    )->count(),

                'en_revision' =>
                    Solicitud::where(
                        'estado',
                        'EN_REVISION'
                    )->count(),

                'documentacion_incompleta' =>
                    Solicitud::where(
                        'estado',
                        'DOCUMENTACION_INCOMPLETA'
                    )->count(),

                'aceptadas' =>
                    Solicitud::where(
                        'estado',
                        'ACEPTADA'
                    )->count(),

                'rechazadas' =>
                    Solicitud::where(
                        'estado',
                        'RECHAZADA'
                    )->count(),

                'alumnos' =>
                    User::where(
                        'role',
                        'alumno'
                    )->count(),

                'personal' =>
                    User::whereIn(
                        'role',
                        [
                            'admin',
                            'profesor',
                        ]
                    )->count(),

                'convocatorias' =>
                    Convocatoria::count(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | LISTAR USUARIOS
    |--------------------------------------------------------------------------
    */

    public function listarUsuarios()
    {
        $usuarios = User::with([
            'carrera',
            'grupoRelacion',
            'carrerasAsignadas',
        ])
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $usuarios,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | COMPATIBILIDAD
    |--------------------------------------------------------------------------
    */

    public function getUsers()
    {
        return $this->listarUsuarios();
    }

    /*
    |--------------------------------------------------------------------------
    | CONTRASEÑA TEMPORAL
    |--------------------------------------------------------------------------
    */

    public function resetPassword(
        Request $request
    ) {
        $data = $request->validate([
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $usuario = User::findOrFail(
            $data['user_id']
        );

        if (
            $usuario->id ===
            $request->user()->id
        ) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'No puedes establecer tu propia contraseña desde este módulo.',
            ], 422);
        }

        $usuario->password =
            Hash::make(
                $data['password']
            );

        /*
        |--------------------------------------------------------------------------
        | OBLIGAR CAMBIO POSTERIOR
        |--------------------------------------------------------------------------
        */

        $usuario->must_change_password =
            true;

        $usuario->save();

        return response()->json([
            'status' => 'success',

            'message' =>
                'Contraseña temporal establecida correctamente.',

            'must_change_password' =>
                true,
        ]);
    }
}