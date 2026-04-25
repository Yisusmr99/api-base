<?php

namespace App\Http\Requests\Api\V1\Transaccion;

use App\Enums\EstadoTransaccion;
use App\Enums\Moneda;
use App\Enums\TipoTransaccion;
use App\Http\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreTransaccionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_transaccion'   => ['required', Rule::enum(TipoTransaccion::class)],
            'moneda'             => ['required', Rule::enum(Moneda::class)],
            'monto'              => ['required', 'numeric', 'min:0.01'],
            'id_cuenta_origen'   => [
                Rule::requiredIf(fn () => in_array($this->tipo_transaccion, [
                    TipoTransaccion::Transferencia->value,
                    TipoTransaccion::Retiro->value,
                ])),
                'nullable',
                'integer',
                'exists:cuentas,id',
                Rule::notIn([$this->id_cuenta_destino]),
            ],
            'id_cuenta_destino'  => [
                Rule::requiredIf(fn () => in_array($this->tipo_transaccion, [
                    TipoTransaccion::Transferencia->value,
                    TipoTransaccion::Deposito->value,
                ])),
                'nullable',
                'integer',
                'exists:cuentas,id',
            ],
            'referencia'         => ['sometimes', 'nullable', 'string', 'max:255'],
            'es_externa'         => ['sometimes', 'boolean'],
            'banco_externo'      => ['required_if:es_externa,true', 'nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_cuenta_origen.not_in' => 'La cuenta de origen no puede ser igual a la cuenta de destino.',
        ];
    }

    protected function passedValidation(): void
    {
        $now = now();

        $this->merge([
            'estado'            => EstadoTransaccion::Pendiente->value,
            'fecha_transaccion' => $now,
            'hora_transaccion'  => $now,
            'es_externa'        => $this->boolean('es_externa', false),
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
