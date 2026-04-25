<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CuentaResource extends JsonResource
{
    protected $hiddenFields = [];

    public function hide(array $fields): self
    {
        $this->hiddenFields = $fields;
        return $this;
    }

    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'id_cliente' => $this->id_cliente,
            'numero_cuenta' => $this->numero_cuenta,
            'saldo' => $this->saldo,
            'saldo_disponible' => $this->saldo_disponible,
            'tipo_cuenta' => $this->tipo_cuenta?->value,
            'moneda' => $this->moneda?->value,
            'fecha_apertura' => $this->fecha_apertura?->toIso8601String(),
            'fecha_cierre' => $this->fecha_cierre?->toIso8601String(),
            'estado' => $this->estado,
            'cliente' => ClienteResource::make($this->whenLoaded('cliente')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];

        foreach ($this->hiddenFields as $field) {
            unset($data[$field]);
        }

        return $data;
    }
}
