<?php

namespace App\Http\Requests\Api\V1\Reporte;

use App\Enums\EstadoTransaccion;
use App\Enums\TipoTransaccion;
use App\Http\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * Filtros para el reporte de historial de transacciones.
 */
class HistorialTransaccionesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'desde'           => ['nullable', 'date'],
            'hasta'           => ['nullable', 'date', 'after_or_equal:desde'],
            'cliente_id'      => ['nullable', 'integer', 'exists:clientes,id'],
            'cuenta_id'       => ['nullable', 'integer', 'exists:cuentas,id'],
            'tipo'            => ['nullable', Rule::enum(TipoTransaccion::class)],
            'estado'          => ['nullable', Rule::enum(EstadoTransaccion::class)],
            'es_externa'      => ['nullable', 'boolean'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::error(
                message: 'Error de validación.',
                errors: $validator->errors()->toArray(),
                status: 422
            )
        );
    }
}
