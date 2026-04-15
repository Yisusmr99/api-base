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
            'saldo' => ['sometimes', 'numeric', 'min:0'],
            'saldo_disponible' => ['sometimes', 'numeric', 'min:0'],
            'tipo_cuenta' => ['required', Rule::enum(TipoCuenta::class)],
            'fecha_apertura' => ['nullable', 'date'],
            'fecha_cierre' => ['nullable', 'date'],
            'moneda' => ['sometimes', Rule::enum(Moneda::class)],
            'estado' => ['sometimes', 'boolean'],
        ];
    }

    protected function passedValidation(): void
    {
        $this->mergeIfMissing([
            'saldo' => 0,
            'saldo_disponible' => 0,
            'moneda' => Moneda::Quetzal->value,
            'estado' => true,
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
