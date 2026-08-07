<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | REGISTRO DE ALUMNO
    |--------------------------------------------------------------------------
    */

    public function register(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | NORMALIZAR CORREO
        |--------------------------------------------------------------------------
        |
        | Corrige casos como:
        |
        | [231650260@alumno.uptex.edu.mx](mailto:231650260@alumno.uptex.edu.mx)
        |
        | y lo convierte simplemente en:
        |
        | 231650260@alumno.uptex.edu.mx
        |
        */

        $email = $this->normalizarEmail(
            $request->input('email')
        );

        $name = trim(
            (string) $request->input('name')
        );

        /*
        |--------------------------------------------------------------------------
        | Sustituimos los datos normalizados en el request
        |--------------------------------------------------------------------------
        */

        $request->merge([
            'name' => $name,
            'email' => $email,
        ]);

        /*
        |--------------------------------------------------------------------------
        | VALIDACIONES
        |--------------------------------------------------------------------------
        */

        $validator = Validator::make(
            $request->all(),
            [
                'name' => [
                    'required',
                    'string',
                    'min:3',
                    'max:255',
                ],

                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    'unique:users,email',

                    /*
                    |--------------------------------------------------------------------------
                    | Correo institucional de alumno.
                    |
                    | Permite:
                    | 231650260@alumno.uptex.edu.mx
                    |--------------------------------------------------------------------------
                    */

                    'regex:/^[a-zA-Z0-9._%+\-]+@alumno\.uptex\.edu\.mx$/i',
                ],

                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'confirmed',
                ],
            ],
            [
                'name.required' =>
                    'El nombre es obligatorio.',

                'name.min' =>
                    'El nombre debe contener al menos 3 caracteres.',

                'email.required' =>
                    'El correo institucional es obligatorio.',

                'email.email' =>
                    'El correo electrónico no tiene un formato válido.',

                'email.unique' =>
                    'Este correo institucional ya se encuentra registrado.',

                'email.regex' =>
                    'Debes utilizar un correo institucional con dominio @alumno.uptex.edu.mx.',

                'password.required' =>
                    'La contraseña es obligatoria.',

                'password.min' =>
                    'La contraseña debe contener al menos 8 caracteres.',

                'password.confirmed' =>
                    'La confirmación de la contraseña no coincide.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    $validator
                        ->errors()
                        ->first(),

                'errors' =>
                    $validator->errors(),
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | OBTENER MATRÍCULA
        |--------------------------------------------------------------------------
        |
        | Si el correo es:
        |
        | 231650260@alumno.uptex.edu.mx
        |
        | la matrícula será:
        |
        | 231650260
        |
        */

        $matricula = $this->obtenerMatriculaDesdeEmail(
            $email
        );

        try {

            /*
            |--------------------------------------------------------------------------
            | GENERAR CÓDIGO
            |--------------------------------------------------------------------------
            */

            $codigoVerificacion =
                strtoupper(
                    Str::random(6)
                );

            /*
            |--------------------------------------------------------------------------
            | CREAR USUARIO
            |--------------------------------------------------------------------------
            */

            $user = User::create([
                'name' =>
                    $name,

                'email' =>
                    $email,

                'matricula' =>
                    $matricula,

                'password' =>
                    Hash::make(
                        $request->password
                    ),

                'role' =>
                    'alumno',

                'carrera_id' =>
                    null,

                'grupo' =>
                    null,

                'verification_code' =>
                    $codigoVerificacion,

                'verification_code_expires_at' =>
                    Carbon::now()
                        ->addMinutes(15),

                'email_verified_at' =>
                    null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | ENVIAR CORREO
            |--------------------------------------------------------------------------
            */

            $this->enviarCorreoVerificacion(
                $user,
                $codigoVerificacion
            );

            /*
            |--------------------------------------------------------------------------
            | RESPUESTA
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'status' => 'success',

                'message' =>
                    'Usuario registrado correctamente. Se envió un código de verificación a tu correo institucional.',

                /*
                |--------------------------------------------------------------------------
                | No enviamos el código de verificación al frontend.
                |--------------------------------------------------------------------------
                */

                'user' => [
                    'id' =>
                        $user->id,

                    'name' =>
                        $user->name,

                    'email' =>
                        $user->email,

                    'matricula' =>
                        $user->matricula,

                    'role' =>
                        $user->role,

                    'email_verified_at' =>
                        $user->email_verified_at,
                ],
            ], 201);

        } catch (\Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Si el usuario alcanzó a crearse pero falló el correo,
            | no exponemos información técnica al frontend.
            |--------------------------------------------------------------------------
            */

            report($e);

            return response()->json([
                'status' => 'error',

                'message' =>
                    'No fue posible completar el registro. Inténtalo nuevamente.',
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | VERIFICAR CORREO
    |--------------------------------------------------------------------------
    */

    public function verifyEmail($code)
    {
        /*
        |--------------------------------------------------------------------------
        | Quitamos espacios y convertimos a mayúsculas.
        |--------------------------------------------------------------------------
        */

        $codigoNormalizado =
            strtoupper(
                preg_replace(
                    '/\s+/',
                    '',
                    trim((string) $code)
                )
            );

        if ($codigoNormalizado === '') {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'Debes ingresar el código de verificación.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Buscar usuario por código
        |--------------------------------------------------------------------------
        */

        $user = User::where(
            'verification_code',
            $codigoNormalizado
        )->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',

                'message' =>
                    'El código de seguridad introducido es incorrecto.',
            ], 400);
        }

        /*
        |--------------------------------------------------------------------------
        | Cuenta ya verificada
        |--------------------------------------------------------------------------
        */

        if ($user->email_verified_at !== null) {

            return response()->json([
                'status' => 'success',

                'message' =>
                    'Esta cuenta ya se encuentra verificada. Ya puedes iniciar sesión.',
            ], 200);
        }

        /*
        |--------------------------------------------------------------------------
        | Comprobar expiración
        |--------------------------------------------------------------------------
        */

        if (
            $user->verification_code_expires_at &&
            Carbon::now()->greaterThan(
                $user->verification_code_expires_at
            )
        ) {

            return response()->json([
                'status' => 'error',

                'message' =>
                    'El código de seguridad ha expirado. Solicita uno nuevo.',
            ], 410);
        }

        try {

            /*
            |--------------------------------------------------------------------------
            | Activar cuenta
            |--------------------------------------------------------------------------
            */

            $user->email_verified_at =
                Carbon::now();

            $user->verification_code =
                null;

            $user->verification_code_expires_at =
                null;

            $user->save();

            return response()->json([
                'status' => 'success',

                'message' =>
                    '¡Cuenta activada correctamente! Ya puedes iniciar sesión.',
            ], 200);

        } catch (\Throwable $e) {

            report($e);

            return response()->json([
                'status' => 'error',

                'message' =>
                    'No fue posible activar la cuenta. Inténtalo nuevamente.',
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | REENVIAR CÓDIGO
    |--------------------------------------------------------------------------
    */

    public function resendToken(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Normalizar correo antes de buscarlo
        |--------------------------------------------------------------------------
        */

        $email = $this->normalizarEmail(
            $request->input('email')
        );

        $request->merge([
            'email' => $email,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Validación
        |--------------------------------------------------------------------------
        */

        $validator = Validator::make(
            $request->all(),
            [
                'email' => [
                    'required',
                    'email',
                    'regex:/^[a-zA-Z0-9._%+\-]+@alumno\.uptex\.edu\.mx$/i',
                ],
            ],
            [
                'email.required' =>
                    'El correo institucional es obligatorio.',

                'email.email' =>
                    'El correo electrónico no tiene un formato válido.',

                'email.regex' =>
                    'Debes utilizar un correo institucional con dominio @alumno.uptex.edu.mx.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',

                'message' =>
                    $validator
                        ->errors()
                        ->first(),
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Buscar usuario
        |--------------------------------------------------------------------------
        */

        $user = User::where(
            'email',
            $email
        )->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',

                'message' =>
                    'No se encontró ningún usuario registrado con este correo electrónico.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Si ya está verificado
        |--------------------------------------------------------------------------
        */

        if ($user->email_verified_at !== null) {

            return response()->json([
                'status' => 'error',

                'message' =>
                    'Esta cuenta ya está activa. Puedes iniciar sesión.',
            ], 400);
        }

        try {

            /*
            |--------------------------------------------------------------------------
            | Generar nuevo código
            |--------------------------------------------------------------------------
            */

            $nuevoCodigo =
                strtoupper(
                    Str::random(6)
                );

            $user->verification_code =
                $nuevoCodigo;

            $user->verification_code_expires_at =
                Carbon::now()
                    ->addMinutes(15);

            /*
            |--------------------------------------------------------------------------
            | Aprovechamos para corregir cualquier correo viejo que haya
            | quedado guardado con formato mailto.
            |--------------------------------------------------------------------------
            */

            $user->email =
                $email;

            /*
            |--------------------------------------------------------------------------
            | Si el alumno no tiene matrícula, la recuperamos del correo.
            |--------------------------------------------------------------------------
            */

            if (
                empty($user->matricula) ||
                $user->matricula === 'null'
            ) {

                $user->matricula =
                    $this
                        ->obtenerMatriculaDesdeEmail(
                            $email
                        );
            }

            $user->save();

            /*
            |--------------------------------------------------------------------------
            | Enviar nuevo código
            |--------------------------------------------------------------------------
            */

            $this->enviarCorreoVerificacion(
                $user,
                $nuevoCodigo,
                true
            );

            return response()->json([
                'status' => 'success',

                'message' =>
                    'Se envió un nuevo código de verificación a tu correo institucional.',
            ], 200);

        } catch (\Throwable $e) {

            report($e);

            return response()->json([
                'status' => 'error',

                'message' =>
                    'No fue posible reenviar el código. Inténtalo nuevamente.',
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | NORMALIZAR CORREO
    |--------------------------------------------------------------------------
    */

    private function normalizarEmail(?string $email): string
    {
        $email =
            trim(
                (string) $email
            );

        /*
        |--------------------------------------------------------------------------
        | Caso:
        |
        | [correo@dominio.com](mailto:correo@dominio.com)
        |--------------------------------------------------------------------------
        */

        if (
            preg_match(
                '/^\[([^\]]+)\]\(mailto:\\?([^)\s]+)\)$/i',
                $email,
                $matches
            )
        ) {

            /*
            |--------------------------------------------------------------------------
            | Preferimos el texto mostrado.
            |--------------------------------------------------------------------------
            */

            $email =
                $matches[1];
        }

        /*
        |--------------------------------------------------------------------------
        | Caso más simple:
        |
        | mailto:correo@dominio.com
        |--------------------------------------------------------------------------
        */

        $email =
            preg_replace(
                '/^mailto:\\?/i',
                '',
                $email
            );

        /*
        |--------------------------------------------------------------------------
        | Eliminar espacios internos accidentales.
        |--------------------------------------------------------------------------
        */

        $email =
            preg_replace(
                '/\s+/',
                '',
                $email
            );

        return strtolower(
            trim($email)
        );
    }


    /*
    |--------------------------------------------------------------------------
    | OBTENER MATRÍCULA DESDE CORREO
    |--------------------------------------------------------------------------
    */

    private function obtenerMatriculaDesdeEmail(
        string $email
    ): ?string {

        $partes =
            explode(
                '@',
                $email
            );

        $usuario =
            $partes[0] ?? null;

        if (!$usuario) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Si la parte anterior al @ es numérica,
        | la utilizamos como matrícula.
        |--------------------------------------------------------------------------
        */

        if (
            preg_match(
                '/^\d+$/',
                $usuario
            )
        ) {
            return $usuario;
        }

        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | ENVIAR CORREO
    |--------------------------------------------------------------------------
    */

    private function enviarCorreoVerificacion(
        User $user,
        string $codigo,
        bool $reenviado = false
    ): void {

        $titulo =
            $reenviado
                ? 'Nuevo código de verificación'
                : 'Código de verificación';

        $mensajePrincipal =
            $reenviado
                ? 'Solicitaste un nuevo código. El código anterior dejó de ser válido.'
                : 'Has solicitado una cuenta en el Sistema de Becas Institucionales de la UPTex.';

        Mail::send(
            [],
            [],
            function ($message) use (
                $user,
                $codigo,
                $titulo,
                $mensajePrincipal
            ) {

                /*
                |--------------------------------------------------------------------------
                | Escapamos nombre y código para el HTML.
                |--------------------------------------------------------------------------
                */

                $nombreSeguro =
                    e($user->name);

                $codigoSeguro =
                    e($codigo);

                $message
                    ->to($user->email)

                    ->subject(
                        $titulo .
                        ' - Sistema de Becas UPTex'
                    )

                    ->html("
                        <div style=\"
                            font-family: Arial, sans-serif;
                            max-width: 600px;
                            margin: 0 auto;
                            background: #ffffff;
                            border: 1px solid #e5e7eb;
                            border-radius: 14px;
                            overflow: hidden;
                        \">

                            <div style=\"
                                background: #00723F;
                                padding: 24px;
                                text-align: center;
                            \">

                                <h2 style=\"
                                    color: #ffffff;
                                    margin: 0;
                                    font-size: 20px;
                                \">
                                    Universidad Politécnica de Texcoco
                                </h2>

                                <p style=\"
                                    color: #d9f1e5;
                                    font-size: 12px;
                                    margin: 5px 0 0;
                                \">
                                    Sistema Institucional de Becas
                                </p>

                            </div>

                            <div style=\"
                                padding: 28px;
                            \">

                                <p>
                                    Hola
                                    <strong>
                                        {$nombreSeguro}
                                    </strong>,
                                </p>

                                <p style=\"
                                    color: #4b5563;
                                    line-height: 1.6;
                                \">
                                    {$mensajePrincipal}
                                </p>

                                <p style=\"
                                    color: #4b5563;
                                \">
                                    Ingresa el siguiente código
                                    en la plataforma:
                                </p>

                                <div style=\"
                                    background: #f4f7f5;
                                    border: 2px dashed #00723F;
                                    padding: 20px;
                                    text-align: center;
                                    margin: 24px 0;
                                    border-radius: 12px;
                                \">

                                    <span style=\"
                                        font-family: monospace;
                                        font-size: 30px;
                                        font-weight: bold;
                                        letter-spacing: 6px;
                                        color: #7A1C33;
                                    \">
                                        {$codigoSeguro}
                                    </span>

                                </div>

                                <p style=\"
                                    color: #6b7280;
                                    font-size: 12px;
                                \">
                                    Este código será válido
                                    durante 15 minutos.
                                </p>

                                <p style=\"
                                    color: #9ca3af;
                                    font-size: 11px;
                                    margin-top: 28px;
                                \">
                                    Si tú no realizaste esta solicitud,
                                    puedes ignorar este mensaje.
                                </p>

                            </div>

                        </div>
                    ");
            }
        );
    }
}