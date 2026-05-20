<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar caché de permisos
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear permisos
        $permissions = [
            'users.index',
            'users.show',
            'users.update',
            'users.delete',
            'transferencias-externas.store',
            'cuentas.search',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Crear rol admin con todos los permisos
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions($permissions);

        // Crear rol cajero (acceso solo a transacciones, sin permisos especiales)
        $cajero = Role::firstOrCreate(['name' => 'cajero']);
        $cajero->syncPermissions([]);

        // Crear rol servicio_al_cliente (acceso a clientes, cuentas y tickets)
        $servicioAlCliente = Role::firstOrCreate(['name' => 'servicio_al_cliente']);
        $servicioAlCliente->syncPermissions([]);

        // Crear rol gerente (acceso a todo menos administración)
        $gerente = Role::firstOrCreate(['name' => 'gerente']);
        $gerente->syncPermissions([]);

        // Crear rol banco con permiso para registrar transferencias externas
        $banco = Role::firstOrCreate(['name' => 'banco']);
        $banco->syncPermissions(['transferencias-externas.store', 'cuentas.search']);
    }
}
