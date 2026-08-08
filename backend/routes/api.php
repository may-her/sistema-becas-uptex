<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\PasswordResetController;

use App\Http\Controllers\AlumnoGestionController;
use App\Http\Controllers\CarreraController;
use App\Http\Controllers\ConvocatoriaController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\PeriodoController;
use App\Http\Controllers\RolAsignacionController;
use App\Http\Controllers\SolicitudBecaController;
use App\Http\Controllers\SuperAdminController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Sistema de Becas UPTex
|
| Roles:
| superadmin
| admin
| profesor
| alumno
|
*/


/* =========================================================
   RUTAS PÚBLICAS
========================================================= */


/*
|--------------------------------------------------------------------------
| AUTENTICACIÓN
|--------------------------------------------------------------------------
*/

Route::post(
    '/login',
    [LoginController::class, 'login']
);

Route::post(
    '/register',
    [RegisterController::class, 'register']
);


/*
|--------------------------------------------------------------------------
| VERIFICACIÓN DE CORREO
|--------------------------------------------------------------------------
*/

Route::get(
    '/verify-email/{code}',
    [RegisterController::class, 'verifyEmail']
);

Route::post(
    '/resend-token',
    [RegisterController::class, 'resendToken']
);


/*
|--------------------------------------------------------------------------
| RECUPERACIÓN DE CONTRASEÑA
|--------------------------------------------------------------------------
*/

Route::post(
    '/forgot-password',
    [PasswordResetController::class, 'enviarCodigo']
);

Route::post(
    '/reset-password',
    [PasswordResetController::class, 'restablecer']
);


/*
|--------------------------------------------------------------------------
| CONVOCATORIAS PÚBLICAS
|--------------------------------------------------------------------------
*/

Route::get(
    '/convocatorias-publicas',
    [ConvocatoriaController::class, 'publica']
);

Route::get(
    '/convocatorias/vigente',
    [ConvocatoriaController::class, 'obtenerVigente']
);

Route::get(
    '/convocatoria/activa',
    [ConvocatoriaController::class, 'getActiva']
);


/*
|--------------------------------------------------------------------------
| CARRERAS PÚBLICAS
|--------------------------------------------------------------------------
|
| Se deja pública porque se necesita para formularios,
| registro, filtros y dashboards.
|
*/

Route::get(
    '/carreras',
    [CarreraController::class, 'index']
);


/* =========================================================
   RUTAS AUTENTICADAS
========================================================= */

Route::middleware(
    'auth:sanctum'
)->group(function () {


    /*
    |--------------------------------------------------------------------------
    | USUARIO ACTUAL
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/user',
        function (Request $request) {

            $usuario = $request->user();

            if (!$usuario) {
                return response()->json([
                    'message' => 'Usuario no autenticado.'
                ], 401);
            }

            try {
                $usuario->load('carrera');
            } catch (\Throwable $e) {
                // Compatibilidad si la relación carrera aún no existe.
            }

            return response()->json([
                'user' => $usuario
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
        [LoginController::class, 'logout']
    );


    /*
    |--------------------------------------------------------------------------
    | SUBIDA GENERAL DE DOCUMENTOS
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/documentos/upload',
        [DocumentoController::class, 'upload']
    );


    /* =====================================================
       ALUMNO
    ====================================================== */

    Route::prefix(
        'alumno'
    )->group(function () {

        Route::get(
            '/convocatoria-actual',
            [ConvocatoriaController::class, 'actual']
        );

        Route::get(
            '/mi-solicitud-activa',
            [SolicitudBecaController::class, 'miSolicitudActiva']
        );

        Route::get(
            '/mis-solicitudes',
            [SolicitudBecaController::class, 'misSolicitudes']
        );

        Route::post(
            '/solicitudes',
            [SolicitudBecaController::class, 'crear']
        );

        Route::post(
            '/solicitudes/{solicitud}/documentos',
            [SolicitudBecaController::class, 'subirDocumento']
        );
    });


    /* =====================================================
       PROFESOR / TUTOR
    ====================================================== */

    Route::prefix(
        'profesor'
    )->group(function () {

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
    });


    /* =====================================================
       ADMIN / JEFE DE CARRERA
    ====================================================== */

    Route::prefix(
        'admin'
    )->group(function () {

        /* -------------------------------------------------
           SOLICITUDES
        ------------------------------------------------- */

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


        /* -------------------------------------------------
           ALUMNOS
        ------------------------------------------------- */

        Route::get(
            '/alumnos',
            [AlumnoGestionController::class, 'index']
        );

        Route::patch(
            '/alumnos/{alumno}',
            [
                AlumnoGestionController::class,
                'actualizar'
            ]
        );


        /* -------------------------------------------------
           STAFF
        ------------------------------------------------- */

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

        Route::delete(
            '/staff/{usuario}',
            [
                RolAsignacionController::class,
                'eliminarStaff'
            ]
        );


        /* -------------------------------------------------
           PERIODOS
        ------------------------------------------------- */

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


        /* -------------------------------------------------
           CONVOCATORIAS
        ------------------------------------------------- */

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

        Route::delete(
            '/convocatorias/{convocatoria}',
            [ConvocatoriaController::class, 'destroy']
        );

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

        Route::patch(
            '/convocatorias/{convocatoria}/publicar',
            [
                ConvocatoriaController::class,
                'publicar'
            ]
        );

        Route::patch(
            '/convocatorias/{convocatoria}/cerrar',
            [
                ConvocatoriaController::class,
                'cerrar'
            ]
        );


        /* -------------------------------------------------
           DOCUMENTOS
        ------------------------------------------------- */

        Route::patch(
            '/documentos/{documento}/observar',
            [
                DocumentoController::class,
                'solicitarCorreccion'
            ]
        );
    });


    /* =====================================================
       SUPERADMIN
    ====================================================== */

    Route::prefix(
        'master'
    )->group(function () {


        /* -------------------------------------------------
           ESTADÍSTICAS
        ------------------------------------------------- */

        Route::get(
            '/stats',
            [
                SuperAdminController::class,
                'getStats'
            ]
        );


        /* -------------------------------------------------
           TODOS LOS USUARIOS
        ------------------------------------------------- */

        Route::get(
            '/usuarios',
            [
                SuperAdminController::class,
                'listarUsuarios'
            ]
        );

        Route::post(
            '/usuarios/{usuario}/forzar-reset',
            [
                AlumnoGestionController::class,
                'forzarResetPassword'
            ]
        );


        /* -------------------------------------------------
           ALUMNOS
        ------------------------------------------------- */

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


        /* -------------------------------------------------
           SOLICITUDES
        ------------------------------------------------- */

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


        /* -------------------------------------------------
           PERSONAL
        ------------------------------------------------- */

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

        Route::delete(
            '/staff/{usuario}',
            [
                RolAsignacionController::class,
                'eliminarStaff'
            ]
        );


        /* -------------------------------------------------
           CONVOCATORIAS
        ------------------------------------------------- */

        Route::get(
            '/convocatorias',
            [
                ConvocatoriaController::class,
                'index'
            ]
        );

        Route::post(
            '/convocatorias',
            [
                ConvocatoriaController::class,
                'store'
            ]
        );

        Route::get(
            '/convocatorias/{convocatoria}',
            [
                ConvocatoriaController::class,
                'show'
            ]
        );

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

        Route::delete(
            '/convocatorias/{convocatoria}',
            [
                ConvocatoriaController::class,
                'destroy'
            ]
        );

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

        Route::patch(
            '/convocatorias/{convocatoria}/publicar',
            [
                ConvocatoriaController::class,
                'publicar'
            ]
        );

        Route::patch(
            '/convocatorias/{convocatoria}/cerrar',
            [
                ConvocatoriaController::class,
                'cerrar'
            ]
        );


        /* -------------------------------------------------
           PERIODOS
        ------------------------------------------------- */

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

        Route::put(
            '/periodos/{periodo}',
            [
                PeriodoController::class,
                'update'
            ]
        );

        Route::patch(
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


        /* -------------------------------------------------
           CARRERAS
        ------------------------------------------------- */

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

        Route::put(
            '/carreras/{carrera}',
            [
                CarreraController::class,
                'update'
            ]
        );

        Route::patch(
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


        /* -------------------------------------------------
           GRUPOS
        ------------------------------------------------- */

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

        Route::put(
            '/grupos/{grupo}',
            [
                GrupoController::class,
                'update'
            ]
        );

        Route::patch(
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

        Route::post(
            '/grupos/{grupo}/alumnos',
            [
                GrupoController::class,
                'asignarAlumno'
            ]
        );

        Route::delete(
            '/grupos/{grupo}/alumnos/{alumno}',
            [
                GrupoController::class,
                'quitarAlumno'
            ]
        );
    });


    /* =====================================================
       RESET DIRECTO DEL SUPERADMIN
    ====================================================== */

    Route::post(
        '/superadmin/reset-password',
        [
            SuperAdminController::class,
            'resetPassword'
        ]
    );
});
