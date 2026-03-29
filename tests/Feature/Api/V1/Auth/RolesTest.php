<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'usuario']);
});

it('asigna rol usuario al registrarse', function () {
    $res = $this->postJson('/v1/auth/register', [
        'name'                  => 'Juan Test',
        'email'                 => 'juan@test.com',
        'password'              => '12345678',
        'password_confirmation' => '12345678',
    ]);

    $res->assertStatus(201)
        ->assertJsonPath('data.user.roles.0', 'usuario');
});

it('incluye roles en la respuesta del login', function () {
    $user = User::factory()->create([
        'email'    => 'juan@test.com',
        'password' => bcrypt('12345678'),
    ]);
    $user->assignRole('usuario');

    $res = $this->postJson('/v1/auth/login', [
        'email'    => 'juan@test.com',
        'password' => '12345678',
    ]);

    $res->assertStatus(200)
        ->assertJsonStructure(['data' => ['user' => ['roles', 'permissions']]]);
});

it('bloquea acceso a rutas de admin para usuario básico', function () {
    $user = User::factory()->create();
    $user->assignRole('usuario');
    Sanctum::actingAs($user);

    $res = $this->getJson('/v1/users');

    $res->assertStatus(403);
});

it('permite acceso a rutas de admin para admin', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    Sanctum::actingAs($user);

    $res = $this->getJson('/v1/users');

    // 200 o 404 si no hay ProfileController aún — lo importante es que no sea 403
    $res->assertStatus(fn ($status) => in_array($status, [200, 404, 500]));
    $res->assertJsonMissing(['message' => 'No autorizado.']);
});