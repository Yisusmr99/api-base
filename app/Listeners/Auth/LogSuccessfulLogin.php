<?php

namespace App\Listeners\Auth;

use App\Models\Mongo\AuditLog;
use App\Services\AuditLogService;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function __construct(private readonly AuditLogService $auditService)
    {
    }

    public function handle(Login $event): void
    {
        $user  = $event->user;
        $email = $user->email ?? null;

        $this->auditService->registrarAccion(
            accion: 'auth.login.success',
            modulo: 'auth',
            mensaje: $email ? "Login exitoso de {$email}" : 'Login exitoso',
            extra: [
                'usuario_id'    => $user->getAuthIdentifier(),
                'usuario_email' => $email,
                'contexto'      => [
                    'guard'    => $event->guard,
                    'remember' => $event->remember ?? false,
                ],
            ],
            severidad: AuditLog::SEVERIDAD_INFO,
        );
    }
}
