<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\RecoveryCode;

class TwoFactorController extends Controller
{
    public function enable(
        Request $request,
        TwoFactorAuthenticationProvider $provider
    ) {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Usuario no autenticado.',
            ], 401);
        }

        if (
            $user->two_factor_secret &&
            $user->two_factor_confirmed_at
        ) {
            return response()->json([
                'message' => '2FA ya está activado.',
            ], 409);
        }

        $user->forceFill([
            'two_factor_secret' =>
                encrypt(
                    $provider->generateSecretKey()
                ),

            'two_factor_recovery_codes' =>
                encrypt(
                    json_encode(
                        Collection::times(
                            8,
                            fn () =>
                                RecoveryCode::generate()
                        )->all()
                    )
                ),

            'two_factor_confirmed_at' =>
                null,
        ])->save();

        return response()->json([
            'status' => 'success',

            'message' =>
                '2FA preparado. Escanea el código QR.',

            'svg' =>
                $user->twoFactorQrCodeSvg(),
        ]);
    }

    public function status(
        Request $request
    ) {
        $user = $request->user();

        return response()->json([
            'enabled' => (bool) (
                $user &&
                $user->two_factor_secret &&
                $user->two_factor_confirmed_at
            ),
        ]);
    }

    public function confirm(
        Request $request,
        TwoFactorAuthenticationProvider $provider
    ) {
        $validated =
            $request->validate([
                'code' => [
                    'required',
                    'digits:6',
                ],
            ]);

        $user = $request->user();

        if (
            ! $user ||
            ! $user->two_factor_secret
        ) {
            return response()->json([
                'message' =>
                    '2FA no está preparado para este usuario.',
            ], 400);
        }

        $secret =
            decrypt(
                $user->two_factor_secret
            );

        if (
            ! $provider->verify(
                $secret,
                $validated['code']
            )
        ) {
            return response()->json([
                'message' =>
                    'Código de autenticación incorrecto.',
            ], 422);
        }

        $user->forceFill([
            'two_factor_confirmed_at' =>
                now(),
        ])->save();

        return response()->json([
            'status' => 'success',

            'message' =>
                'Autenticación de dos factores activada correctamente.',
        ]);
    }

    public function recoveryCodes(
        Request $request
    ) {
        $user = $request->user();

        if (
            ! $user ||
            ! $user->two_factor_recovery_codes
        ) {
            return response()->json([
                'codes' => [],
            ]);
        }

        return response()->json([
            'codes' =>
                $user->recoveryCodes(),
        ]);
    }

    public function disable(
        Request $request
    ) {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Usuario no autenticado.',
            ], 401);
        }

        $user->forceFill([
            'two_factor_secret' =>
                null,

            'two_factor_recovery_codes' =>
                null,

            'two_factor_confirmed_at' =>
                null,
        ])->save();

        return response()->json([
            'status' => 'success',

            'message' =>
                '2FA desactivado correctamente.',
        ]);
    }

    public function challenge(
        Request $request,
        TwoFactorAuthenticationProvider $provider
    ) {
        $validated =
            $request->validate([
                'challenge_token' => [
                    'required',
                    'string',
                ],

                'code' => [
                    'required',
                    'digits:6',
                ],
            ]);

        $cacheKey =
            'two_factor_challenge:' .
            hash(
                'sha256',
                $validated['challenge_token']
            );

        $userId =
            Cache::get(
                $cacheKey
            );

        if (! $userId) {
            return response()->json([
                'message' =>
                    'El desafío 2FA expiró. Inicia sesión nuevamente.',
            ], 401);
        }

        $user =
            User::find(
                $userId
            );

        if (
            ! $user ||
            ! $user->two_factor_secret ||
            ! $user->two_factor_confirmed_at
        ) {
            Cache::forget(
                $cacheKey
            );

            return response()->json([
                'message' =>
                    'La configuración 2FA no es válida.',
            ], 401);
        }

        $secret =
            decrypt(
                $user->two_factor_secret
            );

        if (
            ! $provider->verify(
                $secret,
                $validated['code']
            )
        ) {
            return response()->json([
                'message' =>
                    'Código de autenticación incorrecto.',
            ], 422);
        }

        Cache::forget(
            $cacheKey
        );

        $user->tokens()->delete();

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
                'Código 2FA correcto.',

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
}