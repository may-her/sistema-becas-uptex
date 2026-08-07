<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'El usuario no existe.',
            ], 401);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'La contraseña es incorrecta.',
            ], 401);
        }

        if (!$user->email_verified_at) {
            return response()->json([
                'status' => 'error',
                'message' => 'Debes verificar tu correo antes de iniciar sesión.',
            ], 403);
        }

        // Elimina sesiones/token anteriores
        $user->tokens()->delete();

        // Genera nuevo token Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Acceso concedido.',

            // Dejamos ambos nombres para evitar incompatibilidad
            'token' => $token,
            'access_token' => $token,

            'token_type' => 'Bearer',

            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'matricula' => $user->matricula,
                'carrera_id' => $user->carrera_id,
                'grupo' => $user->grupo,
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
            'message' => 'Sesión cerrada correctamente.',
        ], 200);
    }
}