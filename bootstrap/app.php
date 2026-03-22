<?php

use App\Http\Helpers\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function(){
            Route::middleware('api')
                ->prefix('v1')
                ->group(base_path('routes/api.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        // Registrar alias de middleware de Spatie
        $middleware->alias([
            'role'       => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e) {
            return ApiResponse::error(message: 'No autenticado.', status: 401);
        });

        $exceptions->render(function (NotFoundHttpException $e) {
            return ApiResponse::error(message: 'Recurso no encontrado.', status: 404);
        });

        $exceptions->render(function (ModelNotFoundException $e) {
            return ApiResponse::error(message: 'Recurso no encontrado.', status: 404);
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e) {
            return ApiResponse::error(message: 'Método no permitido.', status: 405);
        });

        $exceptions->render(function (ValidationException $e) {
            return ApiResponse::error(
                message: 'Error de validación.',
                errors: $e->errors(),
                status: 422
            );
        });
    })->create();