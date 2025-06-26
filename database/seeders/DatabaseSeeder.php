<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Usar truncate solo si es necesario para limpiar antes de sembrar
        // Schema::disableForeignKeyConstraints();
        // DB::table('users')->truncate();
        // Schema::enableForeignKeyConstraints();

        $this->call([
            AdminUserSeeder::class,
            ColegioSeeder::class,
            ConvocatoriaSeeder::class,
        ]);
    }
}
