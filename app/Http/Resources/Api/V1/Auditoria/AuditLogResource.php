<?php

namespace App\Http\Resources\Api\V1\Auditoria;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => (string) $this->_id,
            'accion'         => $this->accion,
            'modulo'         => $this->modulo,
            'severidad'      => $this->severidad,
            'mensaje'        => $this->mensaje,
            'usuario_id'     => $this->usuario_id,
            'usuario_email'  => $this->usuario_email,
            'ip'             => $this->ip,
            'user_agent'     => $this->user_agent,
            'http'           => $this->http,
            'payload'        => $this->payload,
            'cambios'        => $this->cambios,
            'contexto'       => $this->contexto,
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}
