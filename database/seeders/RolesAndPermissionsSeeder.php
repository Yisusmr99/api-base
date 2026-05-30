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

        $cajero = Role::firstOrCreate(['name' => 'cajero']);
        $cajero->syncPermissions($permissions);

        $servicioAlCliente = Role::firstOrCreate(['name' => 'servicio_al_cliente']);
        $servicioAlCliente->syncPermissions($permissions);

        $gerente = Role::firstOrCreate(['name' => 'gerente']);
        $gerente->syncPermissions($permissions);

        $banco = Role::firstOrCreate(['name' => 'banco']);
        $banco->syncPermissions($permissions);
    }
}
