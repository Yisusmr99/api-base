<?php

namespace App\Services;

use App\Models\Mongo\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * AuditLogService
 * ---------------
 * Punto único de entrada para registrar eventos en la bitácora MongoDB.
 *
 * Todas las llamadas son tolerantes a fallos: si Mongo está caído o la
 * extensión de PHP no está instalada, se registra el error en el log
 * estándar de Laravel pero NUNCA se propaga la excepción para no romper
 * la operación principal del usuario.
 */
class AuditLogService
{
    /**
     * Campos que se enmascaran si aparecen dentro del payload del request.
     */
    protected const CAMPOS_SENSIBLES = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        'access_token',
        'refresh_token',
        'authorization',
        'api_key',
        'secret',
    ];

    /**
     * Registra una entrada genérica en la bitácora.
     *
     * @param  array<string, mixed>  $atributos
     */
    public function registrar(array $atributos): ?AuditLog
    {
        $atributos = array_merge([
            'severidad'  => AuditLog::SEVERIDAD_INFO,
            'created_at' => now(),
        ], $atributos);

        try {
            return AuditLog::create($atributos);
        } catch (Throwable $e) {
            Log::warning('No se pudo escribir AuditLog en MongoDB', [
                'error'      => $e->getMessage(),
                'atributos'  => $atributos,
            ]);

            return null;
        }
    }

    /**
     * Atajo para registrar acciones contextualizadas con el request HTTP.
     *
     * @param  array<string, mixed>  $extra
     */
    public function registrarAccion(
        string $accion,
        string $modulo,
        ?string $mensaje = null,
        ?Request $request = null,
        array $extra = [],
        string $severidad = AuditLog::SEVERIDAD_INFO,
    ): ?AuditLog {
        $request = $request ?? request();
        $usuario = Auth::user();

        $datos = [
            'accion'         => $accion,
            'modulo'         => $modulo,
            'severidad'      => $severidad,
            'mensaje'        => $mensaje,
            'usuario_id'     => $usuario?->getKey(),
            'usuario_email'  => $usuario?->email ?? null,
            'ip'             => $request?->ip(),
            'user_agent'     => $request?->userAgent(),
            'http'           => $request ? [
                'method'       => $request->method(),
                'url'          => $request->fullUrl(),
                'ruta'         => optional($request->route())->uri(),
                'ruta_nombre'  => optional($request->route())->getName(),
            ] : null,
            'payload'        => $request ? $this->sanitizarPayload($request->all()) : null,
        ];

        return $this->registrar(array_merge($datos, $extra));
    }

    /**
     * Reemplaza los valores de campos sensibles por "***".
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function sanitizarPayload(array $payload): array
    {
        foreach ($payload as $clave => $valor) {
            if (is_array($valor)) {
                $payload[$clave] = $this->sanitizarPayload($valor);
                continue;
            }

            if (in_array(strtolower((string) $clave), self::CAMPOS_SENSIBLES, true)) {
                $payload[$clave] = '***';
            }
        }

        return $payload;
    }

    /**
     * Devuelve solo los campos que cambiaron entre dos arrays asociativos.
     *
     * @param  array<string, mixed>  $antes
     * @param  array<string, mixed>  $despues
     * @return array{antes: array<string, mixed>, despues: array<string, mixed>}
     */
    public function diff(array $antes, array $despues): array
    {
        $cambios = ['antes' => [], 'despues' => []];

        foreach ($despues as $campo => $valorNuevo) {
            $valorAnterior = $antes[$campo] ?? null;
            if ($valorAnterior != $valorNuevo) {
                $cambios['antes'][$campo]   = $valorAnterior;
                $cambios['despues'][$campo] = $valorNuevo;
            }
        }

        return $cambios;
    }
}
