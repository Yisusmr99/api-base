<?php

namespace App\Http\Requests\Api\V1\Cliente;

use App\Http\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombres'            => ['required', 'string', 'max:255'],
            'apellidos'          => ['required', 'string', 'max:255'],
            'dpi'                => ['required', 'string', 'max:20', 'unique:clientes,dpi'],
            'direccion'          => ['required', 'string', 'max:255'],
            'telefono'           => ['required', 'string', 'max:20'],
            'correo_electronico' => ['required', 'string', 'email', 'max:255', 'unique:clientes,correo_electronico'],
            'estado'             => ['sometimes', 'boolean'],
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
