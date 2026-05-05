<?php

namespace App\Listeners\Auth;

use App\Models\Mongo\AuditLog;
use App\Services\AuditLogService;
use Illuminate\Auth\Events\Failed;

class LogFailedLogin
{
    public function __construct(private readonly AuditLogService $auditService)
    {
    }

    public function handle(Failed $event): void
    {
        $email = $event->credentials['email'] ?? 'desconocido';

        $this->auditService->registrarAccion(
            accion: 'auth.login.failed',
            modulo: 'auth',
            mensaje: "Intento de login fallido para {$email}",
            extra: [
                'usuario_email' => $email,
                'contexto'      => [
                    'guard' => $event->guard,
                ],
            ],
            severidad: AuditLog::SEVERIDAD_WARNING,
        );
    }
}
