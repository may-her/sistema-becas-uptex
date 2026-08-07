<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@uptex.edu.mx'],
            [
                'matricula' => 'null',
                'name' => 'Super Administrador',
                'password' => Hash::make('Admin12345*'),
                'role' => 'superadmin',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@uptex.edu.mx'],
            [
                'matricula' => 'null',
                'name' => 'Administrador',
                'password' => Hash::make('Admin12345*'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'profesor@uptex.edu.mx'],
            [
                'matricula' => 'null',
                'name' => 'Profesor',
                'password' => Hash::make('Profesor12345*'),
                'role' => 'profesor',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'alumno@uptex.edu.mx'],
            [
                'matricula' => '231650260',
                'name' => 'Alumno Prueba',
                'password' => Hash::make('Alumno12345*'),
                'role' => 'alumno',
                'email_verified_at' => now(),
            ]
        );
    }
}