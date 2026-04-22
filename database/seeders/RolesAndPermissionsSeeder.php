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

        // Crear rol usuario con permisos básicos
        $user = Role::firstOrCreate(['name' => 'usuario']);
        $user->syncPermissions([
            'users.show',
            'users.update',
        ]);

        // Crear rol banco con permiso para registrar transferencias externas
        $banco = Role::firstOrCreate(['name' => 'banco']);
        $banco->syncPermissions(['transferencias-externas.store', 'cuentas.search']);
    }
}
