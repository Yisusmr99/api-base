<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransaccionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'tipo_transaccion'  => $this->tipo_transaccion?->value,
            'moneda'            => $this->moneda?->value,
            'monto'             => $this->monto,
            'monto_convertido'  => $this->monto_convertido,
            'referencia'        => $this->referencia,
            'estado'            => $this->estado?->value,
            'es_externa'        => $this->es_externa,
            'banco_externo'     => $this->banco_externo,
            'fecha_transaccion' => $this->fecha_transaccion?->toIso8601String(),
            'hora_transaccion'  => $this->hora_transaccion?->toIso8601String(),
            'cuenta_origen'          => CuentaResource::make($this->whenLoaded('cuentaOrigen')),
            'cuenta_destino'         => CuentaResource::make($this->whenLoaded('cuentaDestino')),
            'transferencia_externa'  => TransferenciaExternaResource::make($this->whenLoaded('transferenciaExterna')),
            'created_at'             => $this->created_at?->toIso8601String(),
            'updated_at'             => $this->updated_at?->toIso8601String(),
        ];
    }
}
