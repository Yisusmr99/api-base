<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Base API",
 *     version="1.0.0",
 *     description="API base con autenticación Sanctum y control de acceso por roles y permisos (Spatie Permission)."
 * )
 *
 * @OA\Server(
 *     url="/api",
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Token",
 *     description="Token Sanctum obtenido en POST /auth/login. Formato: Bearer {token}"
 * )
 */
abstract class Controller
{
    //
}
