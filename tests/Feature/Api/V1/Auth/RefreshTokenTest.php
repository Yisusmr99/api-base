<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('renueva el token correctamente', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $res = $this->postJson('/v1/auth/refresh');

    $res->assertStatus(200)
        ->assertJsonStructure(['data' => ['user', 'token']])
        ->assertJson(['status' => true]);
});

it('falla sin autenticación', function () {
    $res = $this->postJson('/v1/auth/refresh');

    $res->assertStatus(401);
});