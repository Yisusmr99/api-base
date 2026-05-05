<?php

namespace App\Http\Resources\Api\V1\Auditoria;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransaccionSnapshotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => (string) $this->_id,
            'transaccion_id_sql' => $this->transaccion_id_sql,
            'motivo'             => $this->motivo,
            'tipo_transaccion'   => $this->tipo_transaccion,
            'estado'             => $this->estado,
            'moneda'             => $this->moneda,
            'monto'              => $this->monto,
            'monto_convertido'   => $this->monto_convertido,
            'es_externa'         => $this->es_externa,
            'banco_externo'      => $this->banco_externo,
            'referencia'         => $this->referencia,
            'cuenta_origen'      => $this->cuenta_origen,
            'cuenta_destino'     => $this->cuenta_destino,
            'registrado_por'     => $this->registrado_por,
            'fecha_transaccion'  => $this->fecha_transaccion?->toIso8601String(),
            'hora_transaccion'   => $this->hora_transaccion?->toIso8601String(),
            'created_at'         => $this->created_at?->toIso8601String(),
        ];
    }
}
