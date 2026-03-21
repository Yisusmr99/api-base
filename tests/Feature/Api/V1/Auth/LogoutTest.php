<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('cierra sesión correctamente', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $res = $this->postJson('/v1/auth/logout');

    $res->assertStatus(200)
        ->assertJson(['status' => true]);

    $this->assertDatabaseCount('personal_access_tokens', 0);
});

it('falla sin autenticación', function () {
    $res = $this->postJson('/v1/auth/logout');

    $res->assertStatus(401);
});