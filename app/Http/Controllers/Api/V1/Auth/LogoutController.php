<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\Mongo\AuditLog;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Helpers\ApiResponse;

class LogoutController extends Controller
{
    public function __construct(private readonly AuditLogService $auditService)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->user()->currentAccessToken()->delete();

        $this->auditService->registrarAccion(
            accion: 'auth.logout',
            modulo: 'auth',
            mensaje: "Logout de {$user->email}",
            request: $request,
            extra: [
                'usuario_id'    => $user->getKey(),
                'usuario_email' => $user->email,
            ],
            severidad: AuditLog::SEVERIDAD_INFO,
        );

        return ApiResponse::success(
            message: 'Sesión cerrada correctamente.'
        );
    }
}
