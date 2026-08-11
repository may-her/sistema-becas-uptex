<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\TwoFactorController;

/*
|--------------------------------------------------------------------------
| SISTEMA
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\AlumnoGestionController;
use App\Http\Controllers\CarreraController;
use App\Http\Controllers\ConvocatoriaController;
use App\Http\Controllers\DictamenController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\PeriodoController;
use App\Http\Controllers\ResultadosController;
use App\Http\Controllers\RolAsignacionController;
use App\Http\Controllers\SolicitudBecaController;
use App\Http\Controllers\SuperAdminController;


/*
|--------------------------------------------------------------------------
| RUTAS PÚBLICAS
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

Route::post(
    '/login',
    [
        LoginController::class,
        'login'
    ]
);


/*
|--------------------------------------------------------------------------
| REGISTRO
|--------------------------------------------------------------------------
*/

Route::post(
    '/register',
    [
        RegisterController::class,
        'register'
    ]
);


/*
|--------------------------------------------------------------------------
| VERIFICAR CORREO
|--------------------------------------------------------------------------
*/

Route::get(
    '/verify-email/{code}',
    [
        RegisterController::class,
        'verifyEmail'
    ]
);

Route::post(
    '/resend-token',
    [
        RegisterController::class,
        'resendToken'
    ]
);


/*
|--------------------------------------------------------------------------
| RECUPERAR CONTRASEÑA
|--------------------------------------------------------------------------
*/

Route::post(
    '/forgot-password',
    [
        PasswordResetController::class,
        'enviarCodigo'
    ]
);

Route::post(
    '/reset-password',
    [
        PasswordResetController::class,
        'restablecer'
    ]
);


/*
|--------------------------------------------------------------------------
| 2FA CHALLENGE
|--------------------------------------------------------------------------
*/

Route::post(
    '/two-factor/challenge',
    [
        TwoFactorController::class,
        'challenge'
    ]
);


/*
|--------------------------------------------------------------------------
| CONVOCATORIAS PÚBLICAS
|--------------------------------------------------------------------------
*/

Route::get(
    '/convocatorias-publicas',
    [
        ConvocatoriaController::class,
        'publica'
    ]
);

Route::get(
    '/convocatorias/vigente',
    [
        ConvocatoriaController::class,
        'obtenerVigente'
    ]
);

Route::get(
    '/convocatoria/activa',
    [
        ConvocatoriaController::class,
        'getActiva'
    ]
);


/*
|--------------------------------------------------------------------------
| CARRERAS PÚBLICAS
|--------------------------------------------------------------------------
*/

Route::get(
    '/carreras',
    [
        CarreraController::class,
        'index'
    ]
);


/*
|--------------------------------------------------------------------------
| RUTAS AUTENTICADAS
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')
    ->group(function () {


        /*
        |--------------------------------------------------------------------------
        | USUARIO ACTUAL
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/user',
            function (Request $request) {

                $usuario =
                    $request->user();

                if (!$usuario) {
                    return response()->json([
                        'status' =>
                            'error',

                        'message' =>
                            'Usuario no autenticado.',
                    ], 401);
                }


                try {

                    $usuario->load([
                        'carrera',
                        'grupoRelacion',
                        'carrerasAsignadas',
                    ]);

                } catch (\Throwable $e) {

                    /*
                     * No tumbamos /user por una
                     * relación opcional.
                     */
                }


                return response()->json([
                    'status' =>
                        'success',

                    'user' =>
                        $usuario,

                    'must_change_password' =>
                        (bool) (
                            $usuario
                                ->must_change_password
                            ??
                            false
                        ),
                ]);
            }
        );


        /*
        |--------------------------------------------------------------------------
        | LOGOUT
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/logout',
            [
                LoginController::class,
                'logout'
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | 2FA
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/two-factor/status',
            [
                TwoFactorController::class,
                'status'
            ]
        );

        Route::post(
            '/two-factor/enable',
            [
                TwoFactorController::class,
                'enable'
            ]
        );

        Route::post(
            '/two-factor/confirm',
            [
                TwoFactorController::class,
                'confirm'
            ]
        );

        Route::get(
            '/two-factor/recovery-codes',
            [
                TwoFactorController::class,
                'recoveryCodes'
            ]
        );

        Route::delete(
            '/two-factor',
            [
                TwoFactorController::class,
                'disable'
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | ALUMNO
        |--------------------------------------------------------------------------
        */

        Route::prefix('alumno')
            ->middleware('role:alumno')
            ->group(function () {


                /*
                |--------------------------------------------------------------------------
                | CONVOCATORIA ACTUAL
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/convocatoria-actual',
                    [
                        ConvocatoriaController::class,
                        'actual'
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | SOLICITUD ACTIVA
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/mi-solicitud-activa',
                    [
                        SolicitudBecaController::class,
                        'miSolicitudActiva'
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | HISTORIAL
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/mis-solicitudes',
                    [
                        SolicitudBecaController::class,
                        'misSolicitudes'
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | CREAR SOLICITUD
                |--------------------------------------------------------------------------
                */

                Route::post(
                    '/solicitudes',
                    [
                        SolicitudBecaController::class,
                        'crear'
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | SUBIR DOCUMENTO
                |--------------------------------------------------------------------------
                */

                Route::post(
                    '/solicitudes/{solicitud}/documentos',
                    [
                        SolicitudBecaController::class,
                        'subirDocumento'
                    ]
                );
            });


        /*
        |--------------------------------------------------------------------------
        | PROFESOR / TUTOR
        |--------------------------------------------------------------------------
        */

        Route::prefix('profesor')
            ->middleware('role:profesor')
            ->group(function () {


                Route::get(
                    '/solicitudes',
                    [
                        SolicitudBecaController::class,
                        'porCarreraAsignada'
                    ]
                );


                Route::patch(
                    '/solicitudes/{solicitud}/estatus',
                    [
                        SolicitudBecaController::class,
                        'actualizarEstatus'
                    ]
                );


                Route::patch(
                    '/solicitudes/{solicitud}/dictamen',
                    [
                        SolicitudBecaController::class,
                        'dictaminar'
                    ]
                );


                Route::patch(
                    '/documentos/{documento}/observar',
                    [
                        DocumentoController::class,
                        'solicitarCorreccion'
                    ]
                );
            });


        /*
        |--------------------------------------------------------------------------
        | ADMIN / JEFE DE CARRERA
        |--------------------------------------------------------------------------
        */

        Route::prefix('admin')
            ->middleware('role:admin')
            ->group(function () {


                /*
                |--------------------------------------------------------------------------
                | SOLICITUDES
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/solicitudes',
                    [
                        SolicitudBecaController::class,
                        'porCarreraAsignada'
                    ]
                );


                Route::patch(
                    '/solicitudes/{solicitud}/estatus',
                    [
                        SolicitudBecaController::class,
                        'actualizarEstatus'
                    ]
                );


                Route::patch(
                    '/solicitudes/{solicitud}/dictamen',
                    [
                        SolicitudBecaController::class,
                        'dictaminar'
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | DOCUMENTOS
                |--------------------------------------------------------------------------
                */

                Route::patch(
                    '/documentos/{documento}/observar',
                    [
                        DocumentoController::class,
                        'solicitarCorreccion'
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | ALUMNOS
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/alumnos',
                    [
                        AlumnoGestionController::class,
                        'index'
                    ]
                );


                Route::patch(
                    '/alumnos/{alumno}',
                    [
                        AlumnoGestionController::class,
                        'actualizar'
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | PERSONAL
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/staff',
                    [
                        RolAsignacionController::class,
                        'listarStaff'
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | PERIODOS
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/periodos',
                    [
                        PeriodoController::class,
                        'index'
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | CONVOCATORIAS
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/convocatorias',
                    [
                        ConvocatoriaController::class,
                        'index'
                    ]
                );
            });


        /*
        |--------------------------------------------------------------------------
        | SUPERADMIN
        |--------------------------------------------------------------------------
        */

        Route::prefix('master')
            ->middleware('role:superadmin')
            ->group(function () {


                /*
                |--------------------------------------------------------------------------
                | ESTADÍSTICAS
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/stats',
                    [
                        SuperAdminController::class,
                        'getStats'
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | USUARIOS
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/usuarios',
                    [
                        SuperAdminController::class,
                        'listarUsuarios'
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | RESET DE CONTRASEÑA
                |--------------------------------------------------------------------------
                */

                Route::post(
                    '/reset-password',
                    [
                        SuperAdminController::class,
                        'resetPassword'
                    ]
                );


                Route::post(
                    '/usuarios/{usuario}/forzar-reset',
                    [
                        AlumnoGestionController::class,
                        'forzarResetPassword'
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | SOLICITUDES
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/solicitudes',
                    [
                        SolicitudBecaController::class,
                        'todas'
                    ]
                );


                Route::patch(
                    '/solicitudes/{solicitud}/estatus',
                    [
                        SolicitudBecaController::class,
                        'actualizarEstatus'
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | DICTAMEN
                |--------------------------------------------------------------------------
                */

                Route::patch(
                    '/solicitudes/{solicitud}/dictamen',
                    [
                        SolicitudBecaController::class,
                        'dictaminar'
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | DICTAMEN FINAL
                |--------------------------------------------------------------------------
                */

                Route::patch(
                    '/solicitudes/{solicitud}/dictamen-final',
                    [
                        DictamenController::class,
                        'guardar'
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | DOCUMENTOS
                |--------------------------------------------------------------------------
                */

                Route::patch(
                    '/documentos/{documento}/observar',
                    [
                        DocumentoController::class,
                        'solicitarCorreccion'
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | ALUMNOS
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/alumnos',
                    [
                        AlumnoGestionController::class,
                        'index'
                    ]
                );


                Route::patch(
                    '/alumnos/{alumno}',
                    [
                        AlumnoGestionController::class,
                        'actualizar'
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | PERSONAL
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/staff',
                    [
                        RolAsignacionController::class,
                        'listarStaff'
                    ]
                );


                Route::post(
                    '/staff',
                    [
                        RolAsignacionController::class,
                        'crearStaff'
                    ]
                );


                Route::patch(
                    '/staff/{usuario}',
                    [
                        RolAsignacionController::class,
                        'actualizarStaff'
                    ]
                );


                Route::put(
                    '/staff/{usuario}',
                    [
                        RolAsignacionController::class,
                        'actualizarStaff'
                    ]
                );


                Route::delete(
                    '/staff/{usuario}',
                    [
                        RolAsignacionController::class,
                        'eliminarStaff'
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | CONVOCATORIAS
                |--------------------------------------------------------------------------
                */


                /*
                | LISTAR
                */

                Route::get(
                    '/convocatorias',
                    [
                        ConvocatoriaController::class,
                        'index'
                    ]
                );


                /*
                | CREAR
                */

                Route::post(
                    '/convocatorias',
                    [
                        ConvocatoriaController::class,
                        'store'
                    ]
                );


                /*
                | VER
                */

                Route::get(
                    '/convocatorias/{convocatoria}',
                    [
                        ConvocatoriaController::class,
                        'show'
                    ]
                );


                /*
                | ACTUALIZAR
                */

                Route::patch(
                    '/convocatorias/{convocatoria}',
                    [
                        ConvocatoriaController::class,
                        'update'
                    ]
                );


                Route::put(
                    '/convocatorias/{convocatoria}',
                    [
                        ConvocatoriaController::class,
                        'update'
                    ]
                );


                /*
                | ELIMINAR
                */

                Route::delete(
                    '/convocatorias/{convocatoria}',
                    [
                        ConvocatoriaController::class,
                        'destroy'
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | PDF
                |--------------------------------------------------------------------------
                */

                Route::post(
                    '/convocatorias/{convocatoria}/archivo',
                    [
                        ConvocatoriaController::class,
                        'reemplazarArchivo'
                    ]
                );


                Route::delete(
                    '/convocatorias/{convocatoria}/archivo',
                    [
                        ConvocatoriaController::class,
                        'eliminarArchivo'
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | PUBLICAR + CORREO
                |--------------------------------------------------------------------------
                |
                | Esta ruta llama a:
                |
                | ConvocatoriaController::publicar()
                |
                | Ahí debe estar:
                |
                | $this->notificarPublicacion(...)
                |
                */

                Route::patch(
                    '/convocatorias/{convocatoria}/publicar',
                    [
                        ConvocatoriaController::class,
                        'publicar'
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | CERRAR + CORREO
                |--------------------------------------------------------------------------
                |
                | Esta ruta llama a:
                |
                | ConvocatoriaController::cerrar()
                |
                | Ahí debe estar:
                |
                | $this->notificarCierre(...)
                |
                */

                Route::patch(
                    '/convocatorias/{convocatoria}/cerrar',
                    [
                        ConvocatoriaController::class,
                        'cerrar'
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | ENVIAR RESULTADOS POR CORREO
                |--------------------------------------------------------------------------
                */

                Route::post(
                    '/convocatorias/{convocatoria}/enviar-resultados',
                    [
                        ResultadosController::class,
                        'enviar'
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | PERIODOS
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/periodos',
                    [
                        PeriodoController::class,
                        'index'
                    ]
                );


                Route::post(
                    '/periodos',
                    [
                        PeriodoController::class,
                        'store'
                    ]
                );


                Route::get(
                    '/periodos/{periodo}',
                    [
                        PeriodoController::class,
                        'show'
                    ]
                );


                Route::patch(
                    '/periodos/{periodo}',
                    [
                        PeriodoController::class,
                        'update'
                    ]
                );


                Route::put(
                    '/periodos/{periodo}',
                    [
                        PeriodoController::class,
                        'update'
                    ]
                );


                Route::patch(
                    '/periodos/{periodo}/cerrar',
                    [
                        PeriodoController::class,
                        'cerrar'
                    ]
                );


                Route::delete(
                    '/periodos/{periodo}',
                    [
                        PeriodoController::class,
                        'destroy'
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | CARRERAS
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/carreras',
                    [
                        CarreraController::class,
                        'index'
                    ]
                );


                Route::post(
                    '/carreras',
                    [
                        CarreraController::class,
                        'store'
                    ]
                );


                Route::get(
                    '/carreras/{carrera}',
                    [
                        CarreraController::class,
                        'show'
                    ]
                );


                Route::patch(
                    '/carreras/{carrera}',
                    [
                        CarreraController::class,
                        'update'
                    ]
                );


                Route::put(
                    '/carreras/{carrera}',
                    [
                        CarreraController::class,
                        'update'
                    ]
                );


                Route::delete(
                    '/carreras/{carrera}',
                    [
                        CarreraController::class,
                        'destroy'
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | GRUPOS
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/grupos',
                    [
                        GrupoController::class,
                        'index'
                    ]
                );


                Route::post(
                    '/grupos',
                    [
                        GrupoController::class,
                        'store'
                    ]
                );


                Route::get(
                    '/grupos/{grupo}',
                    [
                        GrupoController::class,
                        'show'
                    ]
                );


                Route::patch(
                    '/grupos/{grupo}',
                    [
                        GrupoController::class,
                        'update'
                    ]
                );


                Route::put(
                    '/grupos/{grupo}',
                    [
                        GrupoController::class,
                        'update'
                    ]
                );


                Route::delete(
                    '/grupos/{grupo}',
                    [
                        GrupoController::class,
                        'destroy'
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | ASIGNAR ALUMNO
                |--------------------------------------------------------------------------
                */

                Route::post(
                    '/grupos/{grupo}/alumnos',
                    [
                        GrupoController::class,
                        'asignarAlumno'
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | QUITAR ALUMNO
                |--------------------------------------------------------------------------
                */

                Route::delete(
                    '/grupos/{grupo}/alumnos/{alumno}',
                    [
                        GrupoController::class,
                        'quitarAlumno'
                    ]
                );
            });
    });