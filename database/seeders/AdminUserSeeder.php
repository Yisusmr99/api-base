<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario admin base si no existe
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@derbanks.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'estado' => true,
            ]
        );

        // Asignar el rol admin
        $adminUser->assignRole('admin');
    }
}
