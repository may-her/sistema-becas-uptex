<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            /*
            |--------------------------------------------------------------------------
            | 1. PERIODO DE PRUEBA
            |--------------------------------------------------------------------------
            */

            DB::table('periodos')->updateOrInsert(
                [
                    'nombre' => 'Septiembre - Diciembre 2026',
                ],
                [
                    'fecha_inicio' => '2026-09-01',
                    'fecha_fin' => '2026-12-20',
                    'estado' => 'ACTIVO',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $periodoId = DB::table('periodos')
                ->where(
                    'nombre',
                    'Septiembre - Diciembre 2026'
                )
                ->value('id');


            /*
            |--------------------------------------------------------------------------
            | 2. GRUPOS
            |--------------------------------------------------------------------------
            |
            | Conservamos tu grupo real 8vsc3.
            | Creamos grupos demo para las demás carreras.
            |
            */

            $grupos = [
                [
                    'nombre' => '8vsc3',
                    'carrera_id' => 2,
                    'cuatrimestre' => 8,
                    'turno' => 'VESPERTINO',
                ],
                [
                    'nombre' => '7IR1',
                    'carrera_id' => 1,
                    'cuatrimestre' => 7,
                    'turno' => 'MATUTINO',
                ],
                [
                    'nombre' => '6IET1',
                    'carrera_id' => 3,
                    'cuatrimestre' => 6,
                    'turno' => 'MATUTINO',
                ],
                [
                    'nombre' => '5ILT1',
                    'carrera_id' => 4,
                    'cuatrimestre' => 5,
                    'turno' => 'VESPERTINO',
                ],
                [
                    'nombre' => '7LAGE1',
                    'carrera_id' => 5,
                    'cuatrimestre' => 7,
                    'turno' => 'MATUTINO',
                ],
                [
                    'nombre' => '6LCIA1',
                    'carrera_id' => 6,
                    'cuatrimestre' => 6,
                    'turno' => 'VESPERTINO',
                ],
            ];

            foreach ($grupos as $grupo) {

                DB::table('grupos')->updateOrInsert(
                    [
                        'nombre' => $grupo['nombre'],
                        'carrera_id' => $grupo['carrera_id'],
                    ],
                    [
                        'periodo_id' => $periodoId,
                        'cuatrimestre' => $grupo['cuatrimestre'],
                        'turno' => $grupo['turno'],
                        'estado' => 'ACTIVO',
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }


            /*
            |--------------------------------------------------------------------------
            | 3. ACTUALIZAR TU CUENTA REAL
            |--------------------------------------------------------------------------
            |
            | Mayra:
            | ISC
            | Grupo 8vsc3
            |
            */

            $grupoMayra = DB::table('grupos')
                ->where('nombre', '8vsc3')
                ->where('carrera_id', 2)
                ->first();

            DB::table('users')
                ->where(
                    'email',
                    '231650260@alumno.uptex.edu.mx'
                )
                ->update([
                    'role' => 'alumno',

                    'matricula' =>
                        '231650260',

                    'carrera_id' =>
                        2,

                    'grupo_id' =>
                        $grupoMayra?->id,

                    'grupo' =>
                        '8vsc3',

                    'email_verified_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]);


            /*
            |--------------------------------------------------------------------------
            | 4. SUPERADMIN DE DEMOSTRACIÓN
            |--------------------------------------------------------------------------
            */

            DB::table('users')->updateOrInsert(
                [
                    'email' =>
                        'superadmin@uptex.demo',
                ],
                [
                    'name' =>
                        'Super Administrador',

                    'password' =>
                        Hash::make('Prueba123*'),

                    'role' =>
                        'superadmin',

                    'email_verified_at' =>
                        now(),

                    'updated_at' =>
                        now(),

                    'created_at' =>
                        now(),
                ]
            );


            /*
            |--------------------------------------------------------------------------
            | 5. JEFES DE CARRERA
            |--------------------------------------------------------------------------
            */

            for ($carreraId = 1; $carreraId <= 6; $carreraId++) {

                DB::table('users')->updateOrInsert(
                    [
                        'email' =>
                            "jefe{$carreraId}@uptex.demo",
                    ],
                    [
                        'name' =>
                            "Jefe de Carrera {$carreraId}",

                        'password' =>
                            Hash::make('Prueba123*'),

                        'role' =>
                            'admin',

                        'carrera_id' =>
                            $carreraId,

                        'email_verified_at' =>
                            now(),

                        'updated_at' =>
                            now(),

                        'created_at' =>
                            now(),
                    ]
                );
            }


            /*
            |--------------------------------------------------------------------------
            | 6. PROFESORES / TUTORES
            |--------------------------------------------------------------------------
            */

            for ($carreraId = 1; $carreraId <= 6; $carreraId++) {

                DB::table('users')->updateOrInsert(
                    [
                        'email' =>
                            "tutor{$carreraId}@uptex.demo",
                    ],
                    [
                        'name' =>
                            "Profesor Tutor {$carreraId}",

                        'password' =>
                            Hash::make('Prueba123*'),

                        'role' =>
                            'profesor',

                        'carrera_id' =>
                            $carreraId,

                        'email_verified_at' =>
                            now(),

                        'updated_at' =>
                            now(),

                        'created_at' =>
                            now(),
                    ]
                );
            }


            /*
            |--------------------------------------------------------------------------
            | 7. ALUMNOS DE DEMOSTRACIÓN
            |--------------------------------------------------------------------------
            |
            | 48 alumnos ficticios:
            | 8 por carrera.
            |
            | Usamos dominio .demo para no mandar mensajes
            | accidentalmente a personas reales.
            |
            */

            $contador = 1;

            for ($carreraId = 1; $carreraId <= 6; $carreraId++) {

                $grupo = DB::table('grupos')
                    ->where(
                        'carrera_id',
                        $carreraId
                    )
                    ->where(
                        'estado',
                        'ACTIVO'
                    )
                    ->first();

                for ($i = 1; $i <= 8; $i++) {

                    $numero = str_pad(
                        (string) $contador,
                        3,
                        '0',
                        STR_PAD_LEFT
                    );

                    DB::table('users')->updateOrInsert(
                        [
                            'email' =>
                                "alumno{$numero}@uptex.demo",
                        ],
                        [
                            'name' =>
                                "Alumno Demo {$numero}",

                            'password' =>
                                Hash::make('Prueba123*'),

                            'role' =>
                                'alumno',

                            'matricula' =>
                                '2600' . $numero,

                            'carrera_id' =>
                                $carreraId,

                            'grupo_id' =>
                                $grupo?->id,

                            'grupo' =>
                                $grupo?->nombre,

                            /*
                            |--------------------------------------------------------------------------
                            | Los demo NO quedan verificados.
                            |
                            | Así podemos distinguir claramente
                            | las cuentas ficticias.
                            |--------------------------------------------------------------------------
                            */

                            'email_verified_at' =>
                                null,

                            'updated_at' =>
                                now(),

                            'created_at' =>
                                now(),
                        ]
                    );

                    $contador++;
                }
            }


            /*
            |--------------------------------------------------------------------------
            | 8. CONVOCATORIA PUBLICADA
            |--------------------------------------------------------------------------
            |
            | Cierra dentro de dos días para poder probar
            | convocatorias:recordar-cierre.
            |
            */

            DB::table('convocatorias')->updateOrInsert(
                [
                    'nombre' =>
                        'Beca Excelencia Académica 2026',
                ],
                [
                    'periodo_id' =>
                        $periodoId,

                    'descripcion' =>
                        'Apoyo dirigido a estudiantes con alto desempeño académico.',

                    'requisitos' =>
                        'Promedio mínimo 9.0, ser alumno regular y presentar documentación vigente.',

                    'promedio_minimo' =>
                        9.00,

                    'fecha_inicio' =>
                        now()->toDateString(),

                    'fecha_cierre' =>
                        now()
                            ->addDays(2)
                            ->toDateString(),

                    'estado' =>
                        'PUBLICADA',

                    'notificacion_publicada_en' =>
                        null,

                    'recordatorio_2_dias_en' =>
                        null,

                    'updated_at' =>
                        now(),

                    'created_at' =>
                        now(),
                ]
            );


            /*
            |--------------------------------------------------------------------------
            | 9. CONVOCATORIA BORRADOR
            |--------------------------------------------------------------------------
            */

            DB::table('convocatorias')->updateOrInsert(
                [
                    'nombre' =>
                        'Beca Apoyo Socioeconómico 2026',
                ],
                [
                    'periodo_id' =>
                        $periodoId,

                    'descripcion' =>
                        'Apoyo para estudiantes en situación económica vulnerable.',

                    'requisitos' =>
                        'Estudio socioeconómico y documentación comprobatoria.',

                    'promedio_minimo' =>
                        8.00,

                    'fecha_inicio' =>
                        now()
                            ->addDays(10)
                            ->toDateString(),

                    'fecha_cierre' =>
                        now()
                            ->addDays(30)
                            ->toDateString(),

                    'estado' =>
                        'BORRADOR',

                    'notificacion_publicada_en' =>
                        null,

                    'recordatorio_2_dias_en' =>
                        null,

                    'updated_at' =>
                        now(),

                    'created_at' =>
                        now(),
                ]
            );


            /*
            |--------------------------------------------------------------------------
            | 10. CONVOCATORIA CERRADA
            |--------------------------------------------------------------------------
            */

            DB::table('convocatorias')->updateOrInsert(
                [
                    'nombre' =>
                        'Beca Institucional Mayo - Agosto 2026',
                ],
                [
                    'periodo_id' =>
                        $periodoId,

                    'descripcion' =>
                        'Convocatoria histórica para demostración.',

                    'requisitos' =>
                        'Promedio mínimo 8.0.',

                    'promedio_minimo' =>
                        8.00,

                    'fecha_inicio' =>
                        '2026-05-01',

                    'fecha_cierre' =>
                        '2026-08-01',

                    'estado' =>
                        'CERRADA',

                    'notificacion_publicada_en' =>
                        now()
                            ->subMonths(3),

                    'recordatorio_2_dias_en' =>
                        now()
                            ->subMonths(2),

                    'updated_at' =>
                        now(),

                    'created_at' =>
                        now()
                            ->subMonths(4),
                ]
            );


            /*
            |--------------------------------------------------------------------------
            | 11. SOLICITUDES DE DEMOSTRACIÓN
            |--------------------------------------------------------------------------
            */

            $convocatoria = DB::table('convocatorias')
                ->where(
                    'nombre',
                    'Beca Excelencia Académica 2026'
                )
                ->first();

            $alumnos = DB::table('users')
                ->where(
                    'role',
                    'alumno'
                )
                ->whereNotNull(
                    'carrera_id'
                )
                ->limit(35)
                ->get();

            $estados = [
                'PENDIENTE',
                'EN_REVISION',
                'DOCUMENTACION_INCOMPLETA',
                'ACEPTADA',
                'RECHAZADA',
            ];

            $modalidades = [
                'EXCELENCIA_ACADEMICA',
                'SITUACION_SOCIOECONOMICA',
                'DISCAPACIDAD',
            ];

            foreach (
                $alumnos as $indice => $alumno
            ) {

                $estado =
                    $estados[
                        $indice %
                        count($estados)
                    ];

                $modalidad =
                    $modalidades[
                        $indice %
                        count($modalidades)
                    ];

                DB::table('solicitudes')->updateOrInsert(
                    [
                        'user_id' =>
                            $alumno->id,

                        'convocatoria_id' =>
                            $convocatoria->id,
                    ],
                    [
                        'estado' =>
                            $estado,

                        'folio' =>
                            'BEC-2026-' .
                            str_pad(
                                (string) ($indice + 1),
                                4,
                                '0',
                                STR_PAD_LEFT
                            ),

                        'observaciones' =>
                            $estado ===
                            'DOCUMENTACION_INCOMPLETA'
                                ? 'Falta comprobante de domicilio.'
                                : null,

                        'comentario_revision' =>
                            in_array(
                                $estado,
                                [
                                    'ACEPTADA',
                                    'RECHAZADA',
                                ],
                                true
                            )
                                ? 'Solicitud revisada por el comité de becas.'
                                : null,

                        'fecha_revision' =>
                            $estado === 'PENDIENTE'
                                ? null
                                : now(),

                        'modalidad' =>
                            $modalidad,

                        'carrera_id' =>
                            $alumno->carrera_id,

                        'grupo_id' =>
                            $alumno->grupo_id,

                        'created_at' =>
                            now()
                                ->subDays(
                                    rand(1, 20)
                                ),

                        'updated_at' =>
                            now(),
                    ]
                );
            }


            /*
            |--------------------------------------------------------------------------
            | 12. SOLICITUD PRINCIPAL DE MAYRA
            |--------------------------------------------------------------------------
            */

            $mayra = DB::table('users')
                ->where(
                    'email',
                    '231650260@alumno.uptex.edu.mx'
                )
                ->first();

            if (
                $mayra &&
                $convocatoria
            ) {

                DB::table('solicitudes')->updateOrInsert(
                    [
                        'user_id' =>
                            $mayra->id,

                        'convocatoria_id' =>
                            $convocatoria->id,
                    ],
                    [
                        'estado' =>
                            'PENDIENTE',

                        'folio' =>
                            'BEC-2026-MAYRA',

                        'modalidad' =>
                            'EXCELENCIA_ACADEMICA',

                        'carrera_id' =>
                            2,

                        'grupo_id' =>
                            $grupoMayra?->id,

                        'created_at' =>
                            now(),

                        'updated_at' =>
                            now(),
                    ]
                );
            }
        });

        $this->command?->info(
            'Base de demostración creada correctamente.'
        );

        $this->command?->info(
            'Alumno real de pruebas: 231650260@alumno.uptex.edu.mx'
        );

        $this->command?->info(
            'Contraseña de usuarios DEMO: Prueba123*'
        );
    }
}