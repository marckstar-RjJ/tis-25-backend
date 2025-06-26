<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'nombre' => 'Admin',
            'apellido' => 'User',
            'email' => 'admi@gmail.com',
            'password' => Hash::make('admi123'),
            'role' => 'administrador',
        ]);
    }
} 