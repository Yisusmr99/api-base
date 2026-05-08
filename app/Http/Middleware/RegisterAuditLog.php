<?php

namespace App\Http\Middleware;

use App\Models\Mongo\AuditLog;
use App\Services\AuditLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RegisterAuditLog
 * ----------------
 * Middleware que registra automáticamente cada request *autenticado* que
 * modifica estado (POST/PUT/PATCH/DELETE). Las rutas GET no se registran
 * aquí porque generarían demasiado ruido (eso se delega a auditorías
 * específicas si se necesitan).
 *
 * Se aplica a las rutas protegidas. Las acciones de Auth (login/logout)
 * se registran por listeners de eventos para tener mensajes específicos.
 */
class RegisterAuditLog
{
    public function __construct(private readonly AuditLogService $auditService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->debeRegistrar($request)) {
            return $response;
        }

        $status    = $response->getStatusCode();
        $exitoso   = $status < 400;
        $severidad = $exitoso
            ? AuditLog::SEVERIDAD_INFO
            : ($status >= 500 ? AuditLog::SEVERIDAD_ERROR : AuditLog::SEVERIDAD_WARNING);

        $modulo = $this->resolverModulo($request);
        $accion = $this->resolverAccion($request, $exitoso);
        $mensaje = sprintf(
            '%s %s -> %d',
            $request->method(),
            optional($request->route())->uri() ?? $request->path(),
            $status,
        );

        $this->auditService->registrarAccion(
            accion: $accion,
            modulo: $modulo,
            mensaje: $mensaje,
            request: $request,
            extra: [
                'http' => [
                    'method'      => $request->method(),
                    'url'         => $request->fullUrl(),
                    'ruta'        => optional($request->route())->uri(),
                    'ruta_nombre' => optional($request->route())->getName(),
                    'status'      => $status,
                ],
            ],
            severidad: $severidad,
        );

        return $response;
    }

    private function debeRegistrar(Request $request): bool
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return false;
        }

        $rutaNombre = optional($request->route())->getName() ?? '';

        // Login y logout se registran con listeners más específicos.
        if (in_array($rutaNombre, ['auth.login', 'auth.logout', 'auth.refresh', 'auth.register'], true)) {
            return false;
        }

        return true;
    }

    private function resolverModulo(Request $request): string
    {
        $rutaNombre = optional($request->route())->getName() ?? '';

        if ($rutaNombre !== '') {
            return explode('.', $rutaNombre)[0];
        }

        $segmentos = $request->segments();
        return $segmentos[1] ?? ($segmentos[0] ?? 'desconocido');
    }

    private function resolverAccion(Request $request, bool $exitoso): string
    {
        $rutaNombre = optional($request->route())->getName();
        if ($rutaNombre) {
            return $exitoso ? "{$rutaNombre}.success" : "{$rutaNombre}.failed";
        }

        $verbo = strtolower($request->method());
        return $exitoso ? "{$verbo}.success" : "{$verbo}.failed";
    }
}
