<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_ticket' => $this->id_ticket,
            'codigo_ticket' => $this->codigo_ticket,
            'id_cliente' => $this->id_cliente,
            'id_tipo_ticket' => $this->id_tipo_ticket?->value,
            'id_estado_ticket' => $this->id_estado_ticket?->value,
            'id_prioridad' => $this->id_prioridad,
            'asunto' => $this->asunto,
            'descripcion' => $this->descripcion,
            'fecha_creacion' => $this->fecha_creacion?->toIso8601String(),
            'fecha_cierre' => $this->fecha_cierre?->toIso8601String(),
            'canal_origen' => $this->canal_origen,
            'creado_por' => $this->creado_por,
            'observaciones_generales' => $this->observaciones_generales,
            'cliente' => ClienteResource::make($this->whenLoaded('cliente')),
            'creador' => UserResource::make($this->whenLoaded('creador')),
            'asignaciones_count' => $this->whenCounted('asignaciones'),
            'historiales_count' => $this->whenCounted('historiales'),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
