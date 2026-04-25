<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RefreshTokenController;
use App\Http\Controllers\Api\V1\User\ProfileController;
use App\Http\Controllers\Api\V1\Role\RoleController;
use App\Http\Controllers\Api\V1\Cliente\ClienteController;
use App\Http\Controllers\Api\V1\Cuenta\CuentaController;
use App\Http\Controllers\Api\V1\Ticket\TicketController;

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

    Route::prefix('clientes')->group(function () {
        Route::get('/',        [ClienteController::class, 'index'])->name('clientes.index');
        Route::get('/all',     [ClienteController::class, 'indexAll'])->name('clientes.indexAll');
        Route::post('/',       [ClienteController::class, 'store'])->name('clientes.store');
        Route::get('{id}',     [ClienteController::class, 'show'])->name('clientes.show');
        Route::put('{id}',     [ClienteController::class, 'update'])->name('clientes.update');
        Route::delete('{id}',  [ClienteController::class, 'destroy'])->name('clientes.destroy');
    });

    Route::prefix('cuentas')->group(function () {
        Route::get('/',        [CuentaController::class, 'index'])->name('cuentas.index');
        Route::get('/all',     [CuentaController::class, 'indexAll'])->name('cuentas.indexAll');
        Route::post('/',       [CuentaController::class, 'store'])->name('cuentas.store');
        Route::get('{id}',     [CuentaController::class, 'show'])->name('cuentas.show');
        Route::put('{id}',     [CuentaController::class, 'update'])->name('cuentas.update');
        Route::delete('{id}',  [CuentaController::class, 'destroy'])->name('cuentas.destroy');
    });

    Route::prefix('tickets')->group(function () {
        Route::get('/',        [TicketController::class, 'index'])->name('tickets.index');
        Route::get('/all',     [TicketController::class, 'indexAll'])->name('tickets.indexAll');
        Route::get('/filter',  [TicketController::class, 'filter'])->name('tickets.filter');
        Route::get('/cliente/{clienteId}', [TicketController::class, 'getByCliente'])->name('tickets.byCliente');
        Route::post('/',       [TicketController::class, 'store'])->name('tickets.store');
        Route::get('{id}',     [TicketController::class, 'show'])->name('tickets.show');
        Route::put('{id}',     [TicketController::class, 'update'])->name('tickets.update');
        Route::post('{id}/asignar', [TicketController::class, 'assign'])->name('tickets.assign');
        Route::post('{id}/reasignar', [TicketController::class, 'reassign'])->name('tickets.reassign');
        Route::patch('{id}/estado', [TicketController::class, 'changeStatus'])->name('tickets.changeStatus');
        Route::post('{id}/cerrar', [TicketController::class, 'close'])->name('tickets.close');
        Route::delete('{id}',  [TicketController::class, 'destroy'])->name('tickets.destroy');
    });
});
