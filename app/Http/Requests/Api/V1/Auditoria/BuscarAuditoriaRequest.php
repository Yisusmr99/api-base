<?php

namespace App\Http\Requests\Api\V1\Auditoria;

use App\Http\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Filtros disponibles para la búsqueda en la bitácora de auditoría.
 *
 * Todos los parámetros son opcionales y combinables. Coinciden con
 * AuditoriaController::index.
 */
class BuscarAuditoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q'             => ['nullable', 'string', 'max:200'],
            'modulo'        => ['nullable', 'string', 'max:80'],
            'accion'        => ['nullable', 'string', 'max:120'],
            'severidad'     => ['nullable', 'in:info,warning,error,critical'],
            'usuario_id'    => ['nullable', 'integer'],
            'usuario_email' => ['nullable', 'string', 'max:120'],
            'ip'            => ['nullable', 'string', 'max:60'],
            'desde'         => ['nullable', 'date'],
            'hasta'         => ['nullable', 'date', 'after_or_equal:desde'],
            'page'          => ['nullable', 'integer', 'min:1'],
            'per_page'      => ['nullable', 'integer', 'min:1', 'max:200'],
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
