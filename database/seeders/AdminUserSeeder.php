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
        $adminUser->assignRole('admin');

        $gerente = User::firstOrCreate(
            ['email' => 'gerente@derbanks.com'],
            [
                'name' => 'Gerente',
                'password' => Hash::make('Admin123.'),
                'estado' => true,
            ]
        );
        $gerente->assignRole('gerente');

        $servicioCliente = User::firstOrCreate(
            ['email' => 'servicio_cliente@derbanks.com'],
            [
                'name' => 'Servicio al Cliente',
                'password' => Hash::make('Admin123.'),
                'estado' => true,
            ]
        );
        $servicioCliente->assignRole('servicio_al_cliente');

        $cajero = User::firstOrCreate(
            ['email' => 'cajero@derbanks.com'],
            [
                'name' => 'Cajero',
                'password' => Hash::make('Admin123.'),
                'estado' => true,
            ]
        );
        $cajero->assignRole('cajero');
    }
}
