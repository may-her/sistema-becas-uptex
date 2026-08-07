<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;

use App\Http\Controllers\PeriodoController;
use App\Http\Controllers\ConvocatoriaController;
use App\Http\Controllers\SolicitudBecaController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\AlumnoGestionController;
use App\Http\Controllers\RolAsignacionController;
use App\Http\Controllers\SuperAdminController;

use App\Models\Carrera;


/*
|--------------------------------------------------------------------------
| RUTAS PÚBLICAS
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| REGISTRO
|--------------------------------------------------------------------------
*/

Route::post(
    '/register',
    [RegisterController::class, 'register']
)->middleware('throttle:5,1');

Route::get(
    '/verify-email/{code}',
    [RegisterController::class, 'verifyEmail']
)->middleware('throttle:10,1');

Route::post(
    '/resend-token',
    [RegisterController::class, 'resendToken']
)->middleware('throttle:3,1');


/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

Route::post(
    '/login',
    [LoginController::class, 'login']
)->middleware('throttle:5,1');


/*
|--------------------------------------------------------------------------
| RECUPERACIÓN DE CONTRASEÑA
|--------------------------------------------------------------------------
*/

Route::post(
    '/forgot-password',
    [PasswordResetController::class, 'enviarCodigo']
)->middleware('throttle:3,1');

Route::post(
    '/reset-password',
    [PasswordResetController::class, 'restablecer']
)->middleware('throttle:5,1');


/*
|--------------------------------------------------------------------------
| INFORMACIÓN PÚBLICA
|--------------------------------------------------------------------------
*/

// Carreras
Route::get('/carreras', function () {
    return response()->json(
        Carrera::orderBy('nombre')->get()
    );
});


// Convocatorias públicas
Route::get(
    '/convocatorias-publicas',
    [ConvocatoriaController::class, 'publica']
)->middleware('throttle:30,1');


// Convocatoria activa
Route::get(
    '/convocatoria/activa',
    [ConvocatoriaController::class, 'getActiva']
);

Route::get(
    '/convocatorias/vigente',
    [ConvocatoriaController::class, 'obtenerVigente']
);


/*
|--------------------------------------------------------------------------
| RUTAS AUTENTICADAS
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
    'throttle:60,1'
])->group(function () {


    /*
    |--------------------------------------------------------------------------
    | USUARIO AUTENTICADO
    |--------------------------------------------------------------------------
    */

    Route::get('/user', function (Request $request) {

        return response()->json([
            'status' => 'success',
            'user' => $request->user()
        ]);

    });


    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/logout',
        [LoginController::class, 'logout']
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
            | CONVOCATORIA
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/convocatoria-actual',
                [ConvocatoriaController::class, 'actual']
            );


            /*
            |--------------------------------------------------------------------------
            | SOLICITUDES
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/solicitudes',
                [SolicitudBecaController::class, 'crear']
            );

            Route::get(
                '/mis-solicitudes',
                [SolicitudBecaController::class, 'misSolicitudes']
            );

            Route::get(
                '/mi-solicitud-activa',
                [SolicitudBecaController::class, 'miSolicitudActiva']
            );


            /*
            |--------------------------------------------------------------------------
            | DOCUMENTOS
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/solicitudes/{solicitud}/documentos',
                [SolicitudBecaController::class, 'subirDocumento']
            );

        });


    /*
    |--------------------------------------------------------------------------
    | DOCUMENTOS DEL ALUMNO
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:alumno')->group(function () {

        Route::post(
            '/documentos/upload',
            [DocumentoController::class, 'upload']
        );

    });


    /*
    |--------------------------------------------------------------------------
    | ADMINISTRADOR
    |--------------------------------------------------------------------------
    */

    Route::prefix('admin')
        ->middleware('role:admin')
        ->group(function () {


            /*
            |--------------------------------------------------------------------------
            | PERIODOS
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/periodos',
                [PeriodoController::class, 'index']
            );

            Route::post(
                '/periodos',
                [PeriodoController::class, 'store']
            );

            Route::get(
                '/periodos/{periodo}',
                [PeriodoController::class, 'show']
            );

            Route::put(
                '/periodos/{periodo}',
                [PeriodoController::class, 'update']
            );

            Route::patch(
                '/periodos/{periodo}',
                [PeriodoController::class, 'update']
            );

            Route::patch(
                '/periodos/{periodo}/cerrar',
                [PeriodoController::class, 'cerrar']
            );

            Route::delete(
                '/periodos/{periodo}',
                [PeriodoController::class, 'destroy']
            );


            /*
            |--------------------------------------------------------------------------
            | CONVOCATORIAS
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/convocatorias',
                [ConvocatoriaController::class, 'index']
            );

            Route::post(
                '/convocatorias',
                [ConvocatoriaController::class, 'store']
            );

            Route::patch(
                '/convocatorias/{convocatoria}',
                [ConvocatoriaController::class, 'update']
            );

            Route::put(
                '/convocatorias/{convocatoria}',
                [ConvocatoriaController::class, 'update']
            );

            // Subir o reemplazar PDF
            Route::post(
                '/convocatorias/{convocatoria}/archivo',
                [ConvocatoriaController::class, 'reemplazarArchivo']
            );

            // Eliminar solamente PDF
            Route::delete(
                '/convocatorias/{convocatoria}/archivo',
                [ConvocatoriaController::class, 'eliminarArchivo']
            );

            // Publicar
            Route::patch(
                '/convocatorias/{convocatoria}/publicar',
                [ConvocatoriaController::class, 'publicar']
            );

            // Cerrar
            Route::patch(
                '/convocatorias/{convocatoria}/cerrar',
                [ConvocatoriaController::class, 'cerrar']
            );

            // Eliminar convocatoria
            Route::delete(
                '/convocatorias/{convocatoria}',
                [ConvocatoriaController::class, 'destroy']
            );


            /*
            |--------------------------------------------------------------------------
            | SOLICITUDES
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/solicitudes',
                [SolicitudBecaController::class, 'porCarreraAsignada']
            );

            Route::patch(
                '/solicitudes/{solicitud}/estatus',
                [SolicitudBecaController::class, 'actualizarEstatus']
            );

            Route::patch(
                '/solicitudes/{solicitud}/dictamen',
                [SolicitudBecaController::class, 'dictaminar']
            );


            /*
            |--------------------------------------------------------------------------
            | DOCUMENTOS
            |--------------------------------------------------------------------------
            */

            Route::patch(
                '/documentos/{documento}/observar',
                [DocumentoController::class, 'solicitarCorreccion']
            );


            /*
            |--------------------------------------------------------------------------
            | ALUMNOS
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/alumnos',
                [AlumnoGestionController::class, 'index']
            );

            Route::patch(
                '/alumnos/{alumno}',
                [AlumnoGestionController::class, 'actualizar']
            );


            /*
            |--------------------------------------------------------------------------
            | STAFF
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/staff',
                [RolAsignacionController::class, 'listarStaff']
            );

            Route::post(
                '/staff',
                [RolAsignacionController::class, 'crearStaff']
            );

            Route::delete(
                '/staff/{usuario}',
                [RolAsignacionController::class, 'eliminarStaff']
            );

        });


    /*
    |--------------------------------------------------------------------------
    | PROFESOR
    |--------------------------------------------------------------------------
    */

    Route::prefix('profesor')
        ->middleware('role:profesor')
        ->group(function () {


            Route::get(
                '/solicitudes',
                [SolicitudBecaController::class, 'porCarreraAsignada']
            );

            Route::patch(
                '/solicitudes/{solicitud}/estatus',
                [SolicitudBecaController::class, 'actualizarEstatus']
            );

        });


    /*
    |--------------------------------------------------------------------------
    | SUPERADMIN
    |--------------------------------------------------------------------------
    |
    | El rol real es "superadmin".
    | Conservamos /master/... como prefijo para no romper el frontend.
    |--------------------------------------------------------------------------
    */

    Route::prefix('master')
        ->middleware('role:superadmin')
        ->group(function () {


            /*
            |--------------------------------------------------------------------------
            | DASHBOARD
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/stats',
                [SuperAdminController::class, 'getStats']
            );


            /*
            |--------------------------------------------------------------------------
            | USUARIOS
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/usuarios',
                [SuperAdminController::class, 'listarUsuarios']
            );


            /*
            |--------------------------------------------------------------------------
            | PERIODOS
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/periodos',
                [PeriodoController::class, 'index']
            );

            Route::get(
                '/periodos/{periodo}',
                [PeriodoController::class, 'show']
            );


            /*
            |--------------------------------------------------------------------------
            | CONVOCATORIAS
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/convocatorias',
                [ConvocatoriaController::class, 'index']
            );

            Route::post(
                '/convocatorias',
                [ConvocatoriaController::class, 'store']
            );

            Route::patch(
                '/convocatorias/{convocatoria}',
                [ConvocatoriaController::class, 'update']
            );

            Route::put(
                '/convocatorias/{convocatoria}',
                [ConvocatoriaController::class, 'update']
            );

            // Subir o reemplazar PDF
            Route::post(
                '/convocatorias/{convocatoria}/archivo',
                [ConvocatoriaController::class, 'reemplazarArchivo']
            );

            // Eliminar solamente el PDF
            Route::delete(
                '/convocatorias/{convocatoria}/archivo',
                [ConvocatoriaController::class, 'eliminarArchivo']
            );

            // Publicar
            Route::patch(
                '/convocatorias/{convocatoria}/publicar',
                [ConvocatoriaController::class, 'publicar']
            );

            // Cerrar
            Route::patch(
                '/convocatorias/{convocatoria}/cerrar',
                [ConvocatoriaController::class, 'cerrar']
            );

            // Eliminar convocatoria completa
            Route::delete(
                '/convocatorias/{convocatoria}',
                [ConvocatoriaController::class, 'destroy']
            );


            /*
            |--------------------------------------------------------------------------
            | SOLICITUDES
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/solicitudes',
                [SolicitudBecaController::class, 'todas']
            );

            Route::patch(
                '/solicitudes/{solicitud}/estatus',
                [SolicitudBecaController::class, 'actualizarEstatus']
            );


            /*
            |--------------------------------------------------------------------------
            | ALUMNOS
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/alumnos',
                [AlumnoGestionController::class, 'index']
            );

            Route::patch(
                '/alumnos/{alumno}',
                [AlumnoGestionController::class, 'actualizar']
            );


            /*
            |--------------------------------------------------------------------------
            | RESTABLECIMIENTO DE CONTRASEÑA
            |--------------------------------------------------------------------------
            |
            | Esta es la ruta antigua.
            | La conservamos por compatibilidad.
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/usuarios/{usuario}/forzar-reset',
                [AlumnoGestionController::class, 'forzarResetPassword']
            );


            /*
            |--------------------------------------------------------------------------
            | STAFF
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/staff',
                [RolAsignacionController::class, 'listarStaff']
            );

            Route::post(
                '/staff',
                [RolAsignacionController::class, 'crearStaff']
            );

            Route::delete(
                '/staff/{usuario}',
                [RolAsignacionController::class, 'eliminarStaff']
            );

        });


    /*
    |--------------------------------------------------------------------------
    | COMPATIBILIDAD SUPERADMIN
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:superadmin')->group(function () {

        Route::post(
            '/superadmin/reset-password',
            [SuperAdminController::class, 'resetPassword']
        );

    });

});