<?php

namespace App\Http\Requests\Api\V1\Cuenta;

use App\Enums\Moneda;
use App\Enums\TipoCuenta;
use App\Http\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateCuentaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'id_cliente' => ['sometimes', 'integer', 'exists:clientes,id'],
            'numero_cuenta' => ['sometimes', 'string', 'max:255', Rule::unique('cuentas', 'numero_cuenta')->ignore($id)],
            'tipo_cuenta' => ['sometimes', Rule::enum(TipoCuenta::class)],
            'moneda' => ['sometimes', Rule::enum(Moneda::class)],
            'estado' => ['sometimes', 'boolean'],
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
