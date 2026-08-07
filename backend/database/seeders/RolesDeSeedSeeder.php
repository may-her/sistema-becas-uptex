<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesDeSeedSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'master@uptex.edu.mx'],
            [
                'name' => 'Administrador Master',
                'password' => Hash::make('Master1234'),
                'role' => 'master',
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'jefecarrera@uptex.edu.mx'],
            [
                'name' => 'Jefe de Carrera',
                'password' => Hash::make('Admin1234'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'profesor@uptex.edu.mx'],
            [
                'name' => 'Profesor Tutor',
                'password' => Hash::make('Profesor1234'),
                'role' => 'profesor',
                'email_verified_at' => now(),
            ]
        );
    }
}