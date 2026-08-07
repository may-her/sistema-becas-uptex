<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    // Paso 1: el usuario pide el código
    public function enviarCodigo(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        // FIX: por seguridad, siempre respondemos igual exista o no el correo
        // (evita que alguien use esto para adivinar qué correos están registrados)
        if (!$user) {
            return response()->json([
                'status' => 'success',
                'message' => 'Si el correo existe, se envió un código de recuperación.'
            ], 200);
        }

        $codigo = strtoupper(Str::random(6));
        $user->reset_password_code = $codigo;
        $user->reset_password_expires_at = Carbon::now()->addMinutes(15);
        $user->save();

        Mail::send([], [], function ($message) use ($user, $codigo) {
            $message->to($user->email)
                ->subject('🔑 Recuperación de Contraseña - Sistema de Becas UPTex')
                ->html("
                    <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #eee; padding: 20px; border-radius: 12px;'>
                        <h2 style='color: #7A1C33; text-align: center;'>Universidad Politécnica de Texcoco</h2>
                        <p>Hola <strong>{$user->name}</strong>,</p>
                        <p>Solicitaste recuperar tu contraseña. Usa este código (válido por 15 minutos):</p>
                        <div style='background-color: #F3F4F6; border: 2px dashed #7A1C33; padding: 15px; text-align: center; margin: 20px 0; border-radius: 10px;'>
                            <span style='font-family: monospace; font-size: 28px; font-weight: bold; letter-spacing: 5px; color: #007A54;'>{$codigo}</span>
                        </div>
                        <p style='font-size: 11px; color: #6B7280; text-align: center;'>Si tú no solicitaste esto, ignora este correo.</p>
                    </div>
                ");
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Si el correo existe, se envió un código de recuperación.'
        ], 200);
    }

    // Paso 2: verifica el código y cambia la contraseña
    public function restablecer(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'codigo' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $codigoLimpio = strtoupper(trim($data['codigo']));
        $user = User::where('email', $data['email'])
            ->where('reset_password_code', $codigoLimpio)
            ->first();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Código incorrecto.'], 400);
        }

        if (Carbon::now()->greaterThan($user->reset_password_expires_at)) {
            return response()->json(['status' => 'error', 'message' => 'El código ha expirado, solicita uno nuevo.'], 400);
        }

        $user->password = Hash::make($data['password']);
        $user->reset_password_code = null;
        $user->reset_password_expires_at = null;
        $user->tokens()->delete(); // FIX: cierra todas las sesiones activas por seguridad
        $user->save();

        return response()->json(['status' => 'success', 'message' => 'Contraseña actualizada. Ya puedes iniciar sesión.'], 200);
    }
}