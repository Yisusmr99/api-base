<?php

namespace App\Http\Requests\Api\V1\TransferenciaExterna;

use App\Enums\Moneda;
use App\Enums\TipoTransferenciaExterna;
use App\Http\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreTransferenciaExternaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo'                => ['required', Rule::enum(TipoTransferenciaExterna::class)],
            'moneda'              => ['required', Rule::enum(Moneda::class)],
            'monto'               => ['required', 'numeric', 'min:0.01'],
            'banco_externo'       => ['required', 'string', 'max:255'],
            'cuenta_externa'      => ['required', 'string', 'max:255'],
            'codigo_confirmacion' => ['required', 'string', 'max:255', 'unique:transferencias_externas,codigo_confirmacion'],
            'id_cuenta_destino'   => [
                Rule::requiredIf(fn () => $this->tipo === TipoTransferenciaExterna::Entrante->value),
                'nullable',
                'integer',
                'exists:cuentas,id',
            ],
            'id_cuenta_origen'    => [
                Rule::requiredIf(fn () => $this->tipo === TipoTransferenciaExterna::Saliente->value),
                'nullable',
                'integer',
                'exists:cuentas,id',
            ],
            'referencia'          => ['sometimes', 'nullable', 'string', 'max:255'],
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
