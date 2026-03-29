<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RefreshTokenController;
use App\Http\Controllers\Api\V1\User\ProfileController;

Route::prefix('auth')->middleware('throttle:auth')->group(function () {
    Route::post('/register', RegisterController::class)->name('auth.register');
    Route::post('/login', LoginController::class)->name('auth.login');
});

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', LogoutController::class)->name('auth.logout');
        Route::post('/refresh', RefreshTokenController::class)->name('auth.refresh');
    });

    Route::prefix('users')->group(function () {
        Route::get('me', [ProfileController::class, 'show'])->name('users.me');
        Route::put('me', [ProfileController::class, 'update'])->name('users.update');
    });
});

Route::middleware(['auth:sanctum', 'throttle:api', 'role:admin'])->group(function () {
    Route::prefix('users')->group(function () {
        Route::get('/',        [ProfileController::class, 'index'])->name('users.index');
        Route::get('{id}',     [ProfileController::class, 'show'])->name('users.show');
        Route::delete('{id}',  [ProfileController::class, 'destroy'])->name('users.delete');
    });
});
