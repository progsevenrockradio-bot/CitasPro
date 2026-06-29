<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategoriaSeeder::class,
            // Próximas fases:
            // NegocioSeeder::class,
            // ProfesionalSeeder::class,
            // ClienteSeeder::class,
        ]);
    }
}
