<?php

use App\Models\User;

it('registra un usuario correctamente', function () {
    $res = $this->postJson('/v1/auth/register', [
        'name'                  => 'Juan Test',
        'email'                 => 'juan@test.com',
        'password'              => '12345678',
        'password_confirmation' => '12345678',
    ]);

    $res->assertStatus(201)
        ->assertJsonStructure([
            'status', 'message',
            'data' => ['user' => ['id', 'name', 'email'], 'token'],
        ])
        ->assertJson(['status' => true]);

    $this->assertDatabaseHas('users', ['email' => 'juan@test.com']);
});

it('falla si el email ya existe', function () {
    User::factory()->create(['email' => 'juan@test.com']);

    $res = $this->postJson('/v1/auth/register', [
        'name'                  => 'Juan Test',
        'email'                 => 'juan@test.com',
        'password'              => '12345678',
        'password_confirmation' => '12345678',
    ]);

    $res->assertStatus(422)
        ->assertJson(['status' => false]);
});

it('falla si faltan campos requeridos', function () {
    $res = $this->postJson('/v1/auth/register', []);

    $res->assertStatus(422)
        ->assertJsonStructure(['status', 'message', 'errors']);
});