<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Carrera;

class CarrerasSeeder extends Seeder
{
    public function run(): void
    {
        $carreras = [
            ['nombre' => 'Lic. en Administración y Gestión Empresarial', 'clave' => 'admin_empresarial', 'icono' => '💼'],
            ['nombre' => 'Lic. en Comercio Internacional y Aduanas', 'clave' => 'comercio', 'icono' => '🌐'],
            ['nombre' => 'Ing. en Electrónica y Telecomunicaciones', 'clave' => 'electronica', 'icono' => '🔧'],
            ['nombre' => 'Ing. en Robótica', 'clave' => 'robotica', 'icono' => '🤖'],
            ['nombre' => 'Ing. en Sistemas Computacionales', 'clave' => 'sistemas', 'icono' => '💻'],
            ['nombre' => 'Ing. en Logística y Transporte', 'clave' => 'logistica', 'icono' => '📦'],
        ];

        foreach ($carreras as $c) {
            Carrera::firstOrCreate(['clave' => $c['clave']], $c);
        }
    }
}