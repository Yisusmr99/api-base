<?php

namespace App\Listeners\Auth;

use App\Models\Mongo\AuditLog;
use App\Services\AuditLogService;
use Illuminate\Auth\Events\Logout;

class LogLogout
{
    public function __construct(private readonly AuditLogService $auditService)
    {
    }

    public function handle(Logout $event): void
    {
        $user  = $event->user;
        $email = $user?->email ?? null;

        $this->auditService->registrarAccion(
            accion: 'auth.logout',
            modulo: 'auth',
            mensaje: $email ? "Logout de {$email}" : 'Logout (sin usuario asociado)',
            extra: [
                'usuario_id'    => $user?->getAuthIdentifier(),
                'usuario_email' => $email,
                'contexto'      => [
                    'guard' => $event->guard,
                ],
            ],
            severidad: AuditLog::SEVERIDAD_INFO,
        );
    }
}
