<?php

namespace App\Http\Requests\Api\V1\Ticket;

use App\Enums\EstadoTicket;
use App\Enums\TipoTicket;
use App\Http\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'codigo_ticket' => ['sometimes', 'string', 'max:255', Rule::unique('Tickets', 'codigo_ticket')->ignore($id, 'id_ticket')],
            'id_cliente' => ['sometimes', 'integer', 'exists:clientes,id'],
            'id_tipo_ticket' => ['sometimes', Rule::enum(TipoTicket::class)],
            'id_estado_ticket' => ['sometimes', Rule::enum(EstadoTicket::class)],
            'id_prioridad' => ['sometimes', 'integer'],
            'asunto' => ['sometimes', 'string', 'max:255'],
            'descripcion' => ['sometimes', 'string'],
            'fecha_cierre' => ['nullable', 'date'],
            'canal_origen' => ['sometimes', Rule::in(['telefono', 'correo', 'presencial', 'web'])],
            'creado_por' => ['sometimes', 'integer', 'exists:users,id'],
            'observaciones_generales' => ['sometimes', 'string'],
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
