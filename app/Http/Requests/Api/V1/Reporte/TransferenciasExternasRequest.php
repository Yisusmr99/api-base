<?php

namespace App\Http\Requests\Api\V1\Reporte;

use App\Enums\TipoTransferenciaExterna;
use App\Http\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * Filtros para el reporte de transferencias externas.
 */
class TransferenciasExternasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'desde'         => ['nullable', 'date'],
            'hasta'         => ['nullable', 'date', 'after_or_equal:desde'],
            'banco_externo' => ['nullable', 'string', 'max:255'],
            'tipo'          => ['nullable', Rule::enum(TipoTransferenciaExterna::class)],
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
