<?php

namespace App\Http\Requests\Api\V1\Auditoria;

use App\Enums\EstadoTransaccion;
use App\Enums\Moneda;
use App\Enums\TipoTransaccion;
use App\Http\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * Filtros para consultar la colección de snapshots de transacciones en MongoDB.
 */
class BuscarSnapshotsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaccion_id' => ['nullable', 'integer'],
            'cliente_id'     => ['nullable', 'integer'],
            'cuenta_id'      => ['nullable', 'integer'],
            'numero_cuenta'  => ['nullable', 'string', 'max:60'],
            'tipo'           => ['nullable', Rule::enum(TipoTransaccion::class)],
            'estado'         => ['nullable', Rule::enum(EstadoTransaccion::class)],
            'moneda'         => ['nullable', Rule::enum(Moneda::class)],
            'es_externa'     => ['nullable', 'boolean'],
            'banco_externo'  => ['nullable', 'string', 'max:120'],
            'monto_min'      => ['nullable', 'numeric', 'min:0'],
            'monto_max'      => ['nullable', 'numeric', 'gte:monto_min'],
            'desde'          => ['nullable', 'date'],
            'hasta'          => ['nullable', 'date', 'after_or_equal:desde'],
            'page'           => ['nullable', 'integer', 'min:1'],
            'per_page'       => ['nullable', 'integer', 'min:1', 'max:200'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::error(
                message: 'Error de validación.',
                errors: $validator->errors()->toArray(),
                status: 422,
            )
        );
    }
}
