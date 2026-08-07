<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'matricula' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ]);

        $user = User::with('carrera')
            ->where('matricula', $validated['matricula'])
            ->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'La matrícula no está registrada.'
            ], 401);
        }

        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'La contraseña es incorrecta.'
            ], 401);
        }

        if (
            isset($user->status) &&
            in_array($user->status, ['inactivo', 'bloqueado', 'inactive', 'blocked'])
        ) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tu cuenta se encuentra inactiva. Contacta al administrador.'
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Eliminar tokens anteriores
        |--------------------------------------------------------------------------
        */

        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => '¡Ingreso exitoso!',
            'access_token' => $token,
            'token_type' => 'Bearer',

            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'matricula' => $user->matricula,
                'email' => $user->email,
                'role' => $user->role,
                'carrera_id' => $user->carrera_id,
                'carrera' => $user->carrera,
            ],
        ], 200);
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Sesión cerrada correctamente.'
        ]);
    }
}