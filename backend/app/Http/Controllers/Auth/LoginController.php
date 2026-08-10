<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function login(
        Request $request
    ) {
        $validator =
            Validator::make(
                $request->all(),
                [
                    'email' => [
                        'required',
                        'email',
                    ],

                    'password' => [
                        'required',
                        'string',
                    ],
                ]
            );

        if (
            $validator->fails()
        ) {
            return response()->json([
                'status' =>
                    'error',

                'message' =>
                    $validator
                        ->errors()
                        ->first(),
            ], 422);
        }

        $user =
            User::where(
                'email',
                $request->email
            )->first();

        if (! $user) {
            return response()->json([
                'status' =>
                    'error',

                'message' =>
                    'El usuario no existe.',
            ], 401);
        }

        if (
            ! Hash::check(
                $request->password,
                $user->password
            )
        ) {
            return response()->json([
                'status' =>
                    'error',

                'message' =>
                    'La contraseña es incorrecta.',
            ], 401);
        }

        if (
            ! $user->email_verified_at
        ) {
            return response()->json([
                'status' =>
                    'error',

                'message' =>
                    'Debes verificar tu correo antes de iniciar sesión.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | SI EL USUARIO TIENE 2FA
        |--------------------------------------------------------------------------
        |
        | Todavía NO generamos token Sanctum.
        | Primero debe comprobar su código TOTP.
        |
        */

        if (
            $user->two_factor_secret &&
            $user->two_factor_confirmed_at
        ) {
            $challengeToken =
                Str::random(80);

            Cache::put(
                'two_factor_challenge:' .
                hash(
                    'sha256',
                    $challengeToken
                ),

                $user->id,

                now()->addMinutes(5)
            );

            return response()->json([
                'status' =>
                    'two_factor_required',

                'message' =>
                    'Ingresa el código de tu aplicación autenticadora.',

                'two_factor_required' =>
                    true,

                'challenge_token' =>
                    $challengeToken,

                'expires_in' =>
                    300,
            ], 200);
        }

        /*
        |--------------------------------------------------------------------------
        | LOGIN NORMAL SIN 2FA
        |--------------------------------------------------------------------------
        */

        $user
            ->tokens()
            ->delete();

        $token =
            $user
                ->createToken(
                    'auth_token'
                )
                ->plainTextToken;

        return response()->json([
            'status' =>
                'success',

            'message' =>
                'Acceso concedido.',

            'two_factor_required' =>
                false,

            'token' =>
                $token,

            'access_token' =>
                $token,

            'token_type' =>
                'Bearer',

            'user' => [
                'id' =>
                    $user->id,

                'name' =>
                    $user->name,

                'email' =>
                    $user->email,

                'role' =>
                    $user->role,

                'matricula' =>
                    $user->matricula,

                'carrera_id' =>
                    $user->carrera_id,

                'grupo' =>
                    $user->grupo,
            ],
        ], 200);
    }

    public function logout(
        Request $request
    ) {
        $token =
            $request
                ->user()
                ?->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json([
            'status' =>
                'success',

            'message' =>
                'Sesión cerrada correctamente.',
        ], 200);
    }
}