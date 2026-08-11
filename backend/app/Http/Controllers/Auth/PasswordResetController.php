<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | ENVIAR CÓDIGO DE RECUPERACIÓN
    |--------------------------------------------------------------------------
    */

    public function enviarCodigo(
        Request $request
    ) {
        $validated =
            $request->validate([
                'email' => [
                    'required',
                    'email',
                ],
            ]);


        $usuario =
            User::where(
                'email',
                $validated['email']
            )
            ->first();


        /*
        |--------------------------------------------------------------------------
        | NO REVELAR SI EL CORREO EXISTE
        |--------------------------------------------------------------------------
        */

        if (!$usuario) {

            return response()->json([
                'message' =>
                    'Si el correo está registrado, recibirás un código de recuperación.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | CÓDIGO DE 6 DÍGITOS
        |--------------------------------------------------------------------------
        */

        $codigo =
            (string)
            random_int(
                100000,
                999999
            );


        /*
        |--------------------------------------------------------------------------
        | GUARDAR HASH DEL CÓDIGO
        |--------------------------------------------------------------------------
        */

        $usuario->reset_password_code =
            Hash::make(
                $codigo
            );


        $usuario
            ->reset_password_expires_at =
            now()->addMinutes(15);


        $usuario->save();


        /*
        |--------------------------------------------------------------------------
        | URL DEL SISTEMA
        |--------------------------------------------------------------------------
        */

        $frontendUrl =
            env(
                'FRONTEND_URL',
                'http://localhost:5173'
            );


        /*
        |--------------------------------------------------------------------------
        | ENVIAR CORREO
        |--------------------------------------------------------------------------
        */

        try {

            Mail::raw(
                "Hola {$usuario->name}.\n\n" .
                "Tu código para recuperar tu contraseña es:\n\n" .
                "{$codigo}\n\n" .
                "Este código tiene una vigencia de 15 minutos.\n\n" .
                "Puedes regresar al Sistema de Becas desde:\n" .
                "{$frontendUrl}\n\n" .
                "Si tú no solicitaste este cambio, ignora este mensaje.",

                function ($message) use (
                    $usuario
                ) {

                    $message
                        ->to(
                            $usuario->email
                        )
                        ->subject(
                            'Recuperación de contraseña - Sistema de Becas UPTex'
                        );
                }
            );

        } catch (\Throwable $e) {

            report($e);


            if (
                config('app.debug')
            ) {
                return response()->json([
                    'message' =>
                        'No fue posible enviar el correo.',

                    'error' =>
                        $e->getMessage(),

                    /*
                    |--------------------------------------------------------------------------
                    | SOLO PARA DESARROLLO LOCAL
                    |--------------------------------------------------------------------------
                    */

                    'codigo_debug' =>
                        $codigo,
                ], 500);
            }


            return response()->json([
                'message' =>
                    'No fue posible enviar el correo de recuperación.',
            ], 500);
        }


        return response()->json([
            'message' =>
                'Si el correo está registrado, recibirás un código de recuperación.',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | RESTABLECER CONTRASEÑA
    |--------------------------------------------------------------------------
    */

    public function restablecer(
        Request $request
    ) {
        $validated =
            $request->validate([
                'email' => [
                    'required',
                    'email',
                ],

                'code' => [
                    'required',
                    'string',
                ],

                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'confirmed',
                ],
            ]);


        $usuario =
            User::where(
                'email',
                $validated['email']
            )
            ->first();


        if (
            !$usuario ||
            !$usuario->reset_password_code
        ) {
            return response()->json([
                'message' =>
                    'El código es inválido o ha expirado.',
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | EXPIRACIÓN
        |--------------------------------------------------------------------------
        */

        if (
            !$usuario
                ->reset_password_expires_at
            ||
            now()->greaterThan(
                $usuario
                    ->reset_password_expires_at
            )
        ) {

            $usuario->reset_password_code =
                null;

            $usuario
                ->reset_password_expires_at =
                null;

            $usuario->save();


            return response()->json([
                'message' =>
                    'El código ha expirado. Solicita uno nuevo.',
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | VERIFICAR CÓDIGO
        |--------------------------------------------------------------------------
        */

        if (
            !Hash::check(
                $validated['code'],
                $usuario
                    ->reset_password_code
            )
        ) {
            return response()->json([
                'message' =>
                    'El código de recuperación es incorrecto.',
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | NUEVA CONTRASEÑA
        |--------------------------------------------------------------------------
        */

        $usuario->password =
            Hash::make(
                $validated['password']
            );


        $usuario->reset_password_code =
            null;


        $usuario
            ->reset_password_expires_at =
            null;


        /*
        |--------------------------------------------------------------------------
        | YA NO ES TEMPORAL
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasColumn(
                'users',
                'debe_cambiar_password'
            )
        ) {
            $usuario
                ->debe_cambiar_password =
                false;
        }


        if (
            Schema::hasColumn(
                'users',
                'must_change_password'
            )
        ) {
            $usuario
                ->must_change_password =
                false;
        }


        if (
            Schema::hasColumn(
                'users',
                'password_temporal_generada_at'
            )
        ) {
            $usuario
                ->password_temporal_generada_at =
                null;
        }


        $usuario->save();


        /*
        |--------------------------------------------------------------------------
        | CERRAR TOKENS ANTERIORES
        |--------------------------------------------------------------------------
        */

        $usuario
            ->tokens()
            ->delete();


        return response()->json([
            'message' =>
                'Contraseña restablecida correctamente.',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | CAMBIAR CONTRASEÑA TEMPORAL
    |--------------------------------------------------------------------------
    |
    | Se usa cuando SuperAdmin genera una contraseña temporal.
    |
    */

    public function cambiarPasswordTemporal(
        Request $request
    ) {
        $validated =
            $request->validate([
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'confirmed',
                ],
            ]);


        $usuario =
            $request->user();


        if (!$usuario) {
            return response()->json([
                'message' =>
                    'Usuario no autenticado.',
            ], 401);
        }


        /*
        |--------------------------------------------------------------------------
        | VERIFICAR QUE REALMENTE DEBA CAMBIARLA
        |--------------------------------------------------------------------------
        */

        $debeCambiar = false;


        if (
            Schema::hasColumn(
                'users',
                'debe_cambiar_password'
            )
        ) {
            $debeCambiar =
                $debeCambiar ||
                (bool)
                $usuario
                    ->debe_cambiar_password;
        }


        if (
            Schema::hasColumn(
                'users',
                'must_change_password'
            )
        ) {
            $debeCambiar =
                $debeCambiar ||
                (bool)
                $usuario
                    ->must_change_password;
        }


        if (!$debeCambiar) {
            return response()->json([
                'message' =>
                    'Tu contraseña actual no está marcada como temporal.',
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | ACTUALIZAR
        |--------------------------------------------------------------------------
        */

        $usuario->password =
            Hash::make(
                $validated['password']
            );


        if (
            Schema::hasColumn(
                'users',
                'debe_cambiar_password'
            )
        ) {
            $usuario
                ->debe_cambiar_password =
                false;
        }


        if (
            Schema::hasColumn(
                'users',
                'must_change_password'
            )
        ) {
            $usuario
                ->must_change_password =
                false;
        }


        if (
            Schema::hasColumn(
                'users',
                'password_temporal_generada_at'
            )
        ) {
            $usuario
                ->password_temporal_generada_at =
                null;
        }


        $usuario->save();


        /*
        |--------------------------------------------------------------------------
        | INVALIDAR OTROS TOKENS
        |--------------------------------------------------------------------------
        */

        $tokenActual =
            $usuario
                ->currentAccessToken();


        $usuario
            ->tokens()
            ->when(
                $tokenActual,
                function ($query) use (
                    $tokenActual
                ) {
                    return $query
                        ->where(
                            'id',
                            '!=',
                            $tokenActual->id
                        );
                }
            )
            ->delete();


        return response()->json([
            'message' =>
                'Contraseña actualizada correctamente.',

            'must_change_password' =>
                false,

            'debe_cambiar_password' =>
                false,
        ]);
    }
}