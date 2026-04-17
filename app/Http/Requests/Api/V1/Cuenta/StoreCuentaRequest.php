<?php

namespace App\Http\Requests\Api\V1\Cuenta;

use App\Enums\Moneda;
use App\Enums\TipoCuenta;
use App\Http\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreCuentaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_cliente' => ['required', 'integer', 'exists:clientes,id'],
            'numero_cuenta' => ['required', 'string', 'max:255', 'unique:cuentas,numero_cuenta'],
            'saldo_disponible' => ['required', 'numeric', 'gt:0'],
            'tipo_cuenta' => ['required', Rule::enum(TipoCuenta::class)],
            'moneda' => ['sometimes', Rule::enum(Moneda::class)],
        ];
    }

    protected function passedValidation(): void
    {
        $this->mergeIfMissing([
            'moneda' => Moneda::Quetzal->value,
        ]);
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
