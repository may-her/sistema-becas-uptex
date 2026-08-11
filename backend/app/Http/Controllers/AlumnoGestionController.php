<?php

namespace App\Http\Controllers;

use App\Models\Convocatoria;
use App\Models\Solicitud;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AlumnoGestionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LISTAR ALUMNOS
    |--------------------------------------------------------------------------
    |
    | SuperAdmin:
    |   Puede ver todos.
    |
    | Admin:
    |   Solo alumnos de las carreras que administra.
    |
    */

    public function index(Request $request)
    {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuario no autenticado.',
            ], 401);
        }

        $query = User::query()
            ->where('role', 'alumno')
            ->with([
                'carrera',
                'grupoRelacion',
            ]);

        if ($usuario->role === 'admin') {
            $carrerasIds = $usuario
                ->carrerasAsignadas()
                ->pluck('carreras.id');

            $query->whereIn(
                'carrera_id',
                $carrerasIds
            );
        }

        if ($request->filled('carrera_id')) {
            $query->where(
                'carrera_id',
                $request->carrera_id
            );
        }

        if ($request->filled('buscar')) {
            $buscar = trim(
                $request->buscar
            );

            $query->where(
                function ($q) use ($buscar) {
                    $q->where(
                        'name',
                        'like',
                        "%{$buscar}%"
                    )
                    ->orWhere(
                        'matricula',
                        'like',
                        "%{$buscar}%"
                    )
                    ->orWhere(
                        'email',
                        'like',
                        "%{$buscar}%"
                    );
                }
            );
        }

        $alumnos = $query
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $alumnos,
            'alumnos' => $alumnos,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR ALUMNO
    |--------------------------------------------------------------------------
    */

    public function actualizar(
        Request $request,
        User $alumno
    ) {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuario no autenticado.',
            ], 401);
        }

        if ($alumno->role !== 'alumno') {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'El usuario seleccionado no es un alumno.',
            ], 422);
        }

        if (
            !in_array(
                $usuario->role,
                [
                    'admin',
                    'superadmin',
                    'master',
                ],
                true
            )
        ) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'No tienes permisos para modificar alumnos.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | SEGURIDAD ADMIN
        |--------------------------------------------------------------------------
        */

        if ($usuario->role === 'admin') {
            $carrerasIds = $usuario
                ->carrerasAsignadas()
                ->pluck('carreras.id')
                ->map(
                    fn ($id) => (int) $id
                )
                ->toArray();

            if (
                $alumno->carrera_id &&
                !in_array(
                    (int) $alumno->carrera_id,
                    $carrerasIds,
                    true
                )
            ) {
                return response()->json([
                    'status' => 'error',
                    'message' =>
                        'No administras la carrera de este alumno.',
                ], 403);
            }

            if (
                $request->filled('carrera_id') &&
                !in_array(
                    (int) $request->carrera_id,
                    $carrerasIds,
                    true
                )
            ) {
                return response()->json([
                    'status' => 'error',
                    'message' =>
                        'No puedes asignar al alumno a una carrera que no administras.',
                ], 403);
            }
        }

        $data = $request->validate([
            'carrera_id' => [
                'sometimes',
                'nullable',
                'exists:carreras,id',
            ],

            'grupo_id' => [
                'sometimes',
                'nullable',
                'exists:grupos,id',
            ],

            'grupo' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],

            'matricula' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],
        ]);

        try {
            $alumno->update(
                $data
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => 'error',
                'message' =>
                    'No se pudo actualizar el alumno.',
            ], 422);
        }

        $alumno->load([
            'carrera',
            'grupoRelacion',
        ]);

        return response()->json([
            'status' => 'success',
            'message' =>
                'Alumno actualizado correctamente.',
            'data' => $alumno,
            'alumno' => $alumno,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | CONVOCATORIA ACTUAL DEL ALUMNO
    |--------------------------------------------------------------------------
    |
    | GET /api/alumno/convocatoria-actual
    |
    */

    public function convocatoriaActual(
        Request $request
    ) {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'Usuario no autenticado.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | BUSCAR CONVOCATORIA PUBLICADA Y VIGENTE
        |--------------------------------------------------------------------------
        */

        $hoy = now()
            ->toDateString();

        $convocatoria = Convocatoria::query()
            ->where(
                'estado',
                'PUBLICADA'
            )
            ->whereDate(
                'fecha_inicio',
                '<=',
                $hoy
            )
            ->whereDate(
                'fecha_cierre',
                '>=',
                $hoy
            )
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id')
            ->first();

        if (!$convocatoria) {
            return response()->json([
                'status' => 'success',
                'message' =>
                    'No existe una convocatoria vigente.',
                'data' => null,
                'convocatoria' => null,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | CARGAR PERIODO SI LA RELACIÓN EXISTE
        |--------------------------------------------------------------------------
        */

        try {
            $convocatoria->load(
                'periodo'
            );
        } catch (\Throwable $e) {
            /*
             * No bloqueamos el panel si la relación
             * todavía no existe.
             */
        }

        return response()->json([
            'status' => 'success',
            'data' => $convocatoria,
            'convocatoria' =>
                $convocatoria,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | MI SOLICITUD ACTIVA
    |--------------------------------------------------------------------------
    |
    | GET /api/alumno/mi-solicitud-activa
    |
    */

    public function miSolicitudActiva(
        Request $request
    ) {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'Usuario no autenticado.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | PRIMERO BUSCAMOS LA CONVOCATORIA VIGENTE
        |--------------------------------------------------------------------------
        */

        $hoy = now()
            ->toDateString();

        $convocatoria = Convocatoria::query()
            ->where(
                'estado',
                'PUBLICADA'
            )
            ->whereDate(
                'fecha_inicio',
                '<=',
                $hoy
            )
            ->whereDate(
                'fecha_cierre',
                '>=',
                $hoy
            )
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | SI HAY CONVOCATORIA, BUSCAR SOLICITUD DE ESA CONVOCATORIA
        |--------------------------------------------------------------------------
        */

        $query = Solicitud::query()
            ->where(
                'user_id',
                $usuario->id
            );

        if ($convocatoria) {
            $query->where(
                'convocatoria_id',
                $convocatoria->id
            );
        } else {
            /*
             * Si no existe convocatoria vigente,
             * no inventamos una solicitud activa.
             */
            return response()->json([
                'status' => 'success',
                'data' => null,
                'solicitud' => null,
            ]);
        }

        $solicitud = $query
            ->latest('id')
            ->first();

        if (!$solicitud) {
            return response()->json([
                'status' => 'success',
                'message' =>
                    'No tienes una solicitud activa.',
                'data' => null,
                'solicitud' => null,
            ]);
        }

        $this->cargarRelacionesSolicitud(
            $solicitud
        );

        return response()->json([
            'status' => 'success',
            'data' => $solicitud,
            'solicitud' => $solicitud,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | HISTORIAL DE SOLICITUDES
    |--------------------------------------------------------------------------
    |
    | GET /api/alumno/mis-solicitudes
    |
    */

    public function misSolicitudes(
        Request $request
    ) {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'Usuario no autenticado.',
            ], 401);
        }

        $solicitudes = Solicitud::query()
            ->where(
                'user_id',
                $usuario->id
            )
            ->orderByDesc('id')
            ->get();

        foreach (
            $solicitudes as $solicitud
        ) {
            $this->cargarRelacionesSolicitud(
                $solicitud
            );
        }

        return response()->json([
            'status' => 'success',
            'data' => $solicitudes,
            'solicitudes' =>
                $solicitudes,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | CREAR SOLICITUD DE BECA
    |--------------------------------------------------------------------------
    |
    | POST /api/alumno/solicitudes
    |
    */

    public function store(
        Request $request
    ) {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'Usuario no autenticado.',
            ], 401);
        }

        if ($usuario->role !== 'alumno') {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'Solo los alumnos pueden registrar una solicitud.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDACIÓN
        |--------------------------------------------------------------------------
        */

        $data = $request->validate([
            'convocatoria_id' => [
                'required',
                'integer',
                'exists:convocatorias,id',
            ],

            'modalidad' => [
                'required',
                'string',
                'in:EXCELENCIA_ACADEMICA,SITUACION_SOCIOECONOMICA,DISCAPACIDAD,PROYECTO',
            ],

            'porcentaje_solicitado' => [
                'required',
                'numeric',
                'min:1',
                'max:100',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | VALIDAR CONVOCATORIA
        |--------------------------------------------------------------------------
        */

        $convocatoria = Convocatoria::find(
            $data['convocatoria_id']
        );

        if (!$convocatoria) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'La convocatoria seleccionada no existe.',
            ], 404);
        }

        if (
            strtoupper(
                $convocatoria->estado
            ) !== 'PUBLICADA'
        ) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'La convocatoria no se encuentra publicada.',
            ], 422);
        }

        $hoy = now()
            ->startOfDay();

        $fechaInicio = Carbon::parse(
            $convocatoria->fecha_inicio
        )->startOfDay();

        $fechaCierre = Carbon::parse(
            $convocatoria->fecha_cierre
        )->endOfDay();

        if (
            $hoy->lt($fechaInicio) ||
            $hoy->gt($fechaCierre)
        ) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'La convocatoria no se encuentra vigente.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | DATOS ACADÉMICOS
        |--------------------------------------------------------------------------
        */

        if (!$usuario->carrera_id) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'No tienes una carrera asignada.',
            ], 422);
        }

        $grupoId =
            $usuario->grupo_id ??
            null;

        $nombreGrupo =
            $usuario
                ->grupoRelacion
                ?->nombre
            ??
            $usuario->grupo
            ??
            null;

        if (
            !$grupoId &&
            !$nombreGrupo
        ) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'No tienes un grupo asignado.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | EVITAR SOLICITUD DUPLICADA
        |--------------------------------------------------------------------------
        */

        $yaExiste = Solicitud::query()
            ->where(
                'user_id',
                $usuario->id
            )
            ->where(
                'convocatoria_id',
                $convocatoria->id
            )
            ->exists();

        if ($yaExiste) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'Ya tienes una solicitud registrada para esta convocatoria.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | DATOS QUE VAMOS A GUARDAR
        |--------------------------------------------------------------------------
        */

        $datosSolicitud = [
            'user_id' =>
                $usuario->id,

            'convocatoria_id' =>
                $convocatoria->id,

            'carrera_id' =>
                $usuario->carrera_id,

            'modalidad' =>
                $data['modalidad'],

            'porcentaje_solicitado' =>
                $data[
                    'porcentaje_solicitado'
                ],

            'estado' =>
                'PENDIENTE',
        ];

        /*
        |--------------------------------------------------------------------------
        | GRUPO_ID
        |--------------------------------------------------------------------------
        |
        | Solo se guarda si la columna realmente existe.
        |
        */

        if (
            Schema::hasColumn(
                'solicitudes',
                'grupo_id'
            )
        ) {
            $datosSolicitud[
                'grupo_id'
            ] = $grupoId;
        }

        /*
        |--------------------------------------------------------------------------
        | GRUPO TEXTO
        |--------------------------------------------------------------------------
        |
        | Esto evita que vuelva a aparecer:
        |
        | Unknown column 'grupo'
        |
        | Si la columna no existe simplemente no la usamos.
        |
        */

        if (
            Schema::hasColumn(
                'solicitudes',
                'grupo'
            )
        ) {
            $datosSolicitud[
                'grupo'
            ] = $nombreGrupo;
        }

        /*
        |--------------------------------------------------------------------------
        | CREAR
        |--------------------------------------------------------------------------
        */

        try {
            $solicitud =
                Solicitud::create(
                    $datosSolicitud
                );

        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => 'error',
                'message' =>
                    'No fue posible registrar la solicitud.',
                'error' =>
                    config('app.debug')
                        ? $e->getMessage()
                        : null,
            ], 500);
        }

        $this->cargarRelacionesSolicitud(
            $solicitud
        );

        return response()->json([
            'status' => 'success',
            'message' =>
                'Solicitud registrada correctamente.',
            'data' => $solicitud,
            'solicitud' =>
                $solicitud,
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | CARGAR RELACIONES DE UNA SOLICITUD
    |--------------------------------------------------------------------------
    |
    | Las relaciones básicas no deben impedir que cargue el panel.
    |
    */

    private function cargarRelacionesSolicitud(
        Solicitud $solicitud
    ): void {
        /*
        |--------------------------------------------------------------------------
        | RELACIONES SEGURAS
        |--------------------------------------------------------------------------
        */

        try {
            $solicitud->load([
                'usuario',
                'convocatoria',
                'carrera',
                'grupoRelacion',
                'revisor',
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        /*
        |--------------------------------------------------------------------------
        | DOCUMENTOS
        |--------------------------------------------------------------------------
        |
        | Como todavía estamos comprobando cómo se llama tu tabla
        | real de documentos, no permitimos que esa relación tumbe
        | todo el dashboard.
        |
        */

        try {
            $solicitud->load(
                'documentos'
            );
        } catch (\Throwable $e) {
            report($e);

            /*
             * El frontend recibirá una colección vacía
             * en vez de provocar error 500.
             */
            $solicitud->setRelation(
                'documentos',
                collect()
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | FORZAR RESET DE CONTRASEÑA
    |--------------------------------------------------------------------------
    */

    public function forzarResetPassword(
        Request $request,
        User $usuario
    ) {
        $administrador =
            $request->user();

        if (
            !$administrador ||
            !in_array(
                $administrador->role,
                [
                    'superadmin',
                    'master',
                ],
                true
            )
        ) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'No tienes permisos para realizar esta acción.',
            ], 403);
        }

        if (
            $usuario->id ===
            $administrador->id
        ) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'No puedes realizar esta acción sobre tu propia cuenta.',
            ], 400);
        }

        $codigo =
            strtoupper(
                Str::random(6)
            );

        $usuario->reset_password_code =
            $codigo;

        $usuario
            ->reset_password_expires_at =
            Carbon::now()
                ->addMinutes(15);

        try {
            $usuario->save();
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => 'error',
                'message' =>
                    'No se pudo procesar la solicitud.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | ENVIAR CORREO
        |--------------------------------------------------------------------------
        */

        try {
            Mail::send(
                [],
                [],
                function ($message) use (
                    $usuario,
                    $codigo
                ) {
                    $message
                        ->to(
                            $usuario->email
                        )
                        ->subject(
                            'Restablecimiento de contraseña - UPTex'
                        )
                        ->html("
                            <div style='
                                font-family: Arial, sans-serif;
                                max-width: 600px;
                                margin: 0 auto;
                                border: 1px solid #eee;
                                padding: 20px;
                                border-radius: 12px;
                            '>

                                <h2 style='
                                    color: #7A1C33;
                                    text-align: center;
                                '>
                                    Universidad Politécnica de Texcoco
                                </h2>

                                <p>
                                    Hola
                                    <strong>
                                        {$usuario->name}
                                    </strong>,
                                </p>

                                <p>
                                    Un administrador del sistema solicitó
                                    el restablecimiento de tu contraseña.
                                </p>

                                <p>
                                    Este código es válido durante
                                    15 minutos:
                                </p>

                                <div style='
                                    background-color: #F3F4F6;
                                    border: 2px dashed #7A1C33;
                                    padding: 15px;
                                    text-align: center;
                                    margin: 20px 0;
                                    border-radius: 10px;
                                '>

                                    <span style='
                                        font-family: monospace;
                                        font-size: 28px;
                                        font-weight: bold;
                                        letter-spacing: 5px;
                                        color: #007A54;
                                    '>
                                        {$codigo}
                                    </span>

                                </div>

                                <p style='
                                    font-size: 11px;
                                    color: #6B7280;
                                    text-align: center;
                                '>
                                    Si tú no solicitaste esto,
                                    contacta a control escolar.
                                </p>

                            </div>
                        ");
                }
            );

        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => 'error',
                'message' =>
                    'El código fue generado, pero no pudo enviarse el correo.',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' =>
                'Código de recuperación enviado al usuario.',
        ]);
    }
}