<?php

namespace App\Http\Requests\Api\V1\Reporte;

use App\Enums\Moneda;
use App\Enums\TipoCuenta;
use App\Http\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * Filtros para el reporte de listado de cuentas.
 */
class ListadoCuentasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente_id'  => ['nullable', 'integer', 'exists:clientes,id'],
            'tipo_cuenta' => ['nullable', Rule::enum(TipoCuenta::class)],
            'moneda'      => ['nullable', Rule::enum(Moneda::class)],
            'estado'      => ['nullable', 'boolean'],
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
