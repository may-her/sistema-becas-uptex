<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Llamamos a nuestro seeder de usuariosinstitucionales
        $this->call([
            UserSeeder::class,
        ]);
    }
}