<?php

use App\Models\User;

it('hace login correctamente', function () {
    User::factory()->create([
        'email'    => 'juan@test.com',
        'password' => bcrypt('12345678'),
    ]);

    $res = $this->postJson('/v1/auth/login', [
        'email'    => 'juan@test.com',
        'password' => '12345678',
    ]);

    $res->assertStatus(200)
        ->assertJsonStructure(['data' => ['user', 'token']])
        ->assertJson(['status' => true]);
});

it('falla con credenciales incorrectas', function () {
    User::factory()->create(['email' => 'juan@test.com']);

    $res = $this->postJson('/v1/auth/login', [
        'email'    => 'juan@test.com',
        'password' => 'password_incorrecto',
    ]);

    $res->assertStatus(401)
        ->assertJson(['status' => false]);
});