<?php

namespace App\Models\Mongo;

use MongoDB\Laravel\Eloquent\Model as MongoModel;

/**
 * AuditLog
 * --------
 * Documento NoSQL que representa una entrada en la bitácora de auditoría.
 * Se guarda en la colección "audit_logs" de la base MongoDB configurada.
 *
 * Campos esperados:
 *  - accion       string   Nombre de la acción ("login.success", "cuenta.update", etc.)
 *  - modulo       string   Módulo lógico ("auth", "clientes", "cuentas", ...)
 *  - severidad    string   info | warning | error | critical
 *  - usuario_id   ?int     Id del usuario que ejecutó la acción (si aplica)
 *  - usuario_email ?string Email cacheado para búsquedas sin join
 *  - ip           ?string  IP de origen
 *  - user_agent   ?string  User agent del request
 *  - http         ?array   { method, url, ruta_nombre, status }
 *  - payload      ?array   Datos del request sanitizado (sin contraseñas/tokens)
 *  - cambios      ?array   { antes: {...}, despues: {...} }
 *  - contexto     ?array   Cualquier metadata extra
 *  - mensaje      ?string  Descripción legible
 *  - created_at   datetime
 */
class AuditLog extends MongoModel
{
    protected $connection = 'mongodb';

    protected $collection = 'audit_logs';

    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'usuario_id' => 'integer',
        'http'       => 'array',
        'payload'    => 'array',
        'cambios'    => 'array',
        'contexto'   => 'array',
        'created_at' => 'datetime',
    ];

    public const SEVERIDAD_INFO     = 'info';
    public const SEVERIDAD_WARNING  = 'warning';
    public const SEVERIDAD_ERROR    = 'error';
    public const SEVERIDAD_CRITICAL = 'critical';
}
