<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RefreshTokenController;
use App\Http\Controllers\Api\V1\User\ProfileController;
use App\Http\Controllers\Api\V1\Role\RoleController;

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
        Route::post('/',       [ProfileController::class, 'store'])->name('users.store');
        Route::get('{id}',     [ProfileController::class, 'showById'])->name('users.show');
        Route::put('{id}',     [ProfileController::class, 'updateById'])->name('users.updateById');
        Route::delete('{id}',  [ProfileController::class, 'destroy'])->name('users.delete');
    });

    Route::prefix('roles')->group(function () {
        Route::get('/',        [RoleController::class, 'index'])->name('roles.index');
        Route::get('/all',     [RoleController::class, 'indexAll'])->name('roles.indexAll');
        Route::post('/',       [RoleController::class, 'store'])->name('roles.store');
        Route::get('{id}',     [RoleController::class, 'show'])->name('roles.show');
        Route::put('{id}',     [RoleController::class, 'update'])->name('roles.update');
        Route::delete('{id}',  [RoleController::class, 'destroy'])->name('roles.destroy'); 
    });
});
