<?php

namespace App\Http\Requests\Api\V1\Cliente;

use App\Http\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'nombres'            => ['sometimes', 'string', 'max:255'],
            'apellidos'          => ['sometimes', 'string', 'max:255'],
            'dpi'                => ['sometimes', 'string', 'max:20', Rule::unique('clientes', 'dpi')->ignore($id)],
            'direccion'          => ['sometimes', 'string', 'max:255'],
            'telefono'           => ['sometimes', 'string', 'max:20'],
            'correo_electronico' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('clientes', 'correo_electronico')->ignore($id)],
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
