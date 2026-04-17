<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\V1\CuentaResource;

class ClienteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'nombres'             => $this->nombres,
            'apellidos'           => $this->apellidos,
            'dpi'                 => $this->dpi,
            'direccion'           => $this->direccion,
            'telefono'            => $this->telefono,
            'correo_electronico'  => $this->correo_electronico,
            'estado'              => $this->estado,
            'cuentas'             => CuentaResource::collection($this->whenLoaded('cuentas')),
            'created_at'          => $this->created_at !== null ? $this->created_at->toIso8601String() : null,
            'updated_at'          => $this->updated_at !== null ? $this->updated_at->toIso8601String() : null,
        ];
    }
}
