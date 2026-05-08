<?php

namespace App\Http\Resources\Api\V1\Auditoria;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransaccionSnapshotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->resource->getKey(),
            'transaccion_id_sql' => $this->transaccion_id_sql !== null ? (int) $this->transaccion_id_sql : null,
            'motivo' => $this->motivo,
            'tipo_transaccion' => $this->tipo_transaccion,
            'estado' => $this->estado,
            'moneda' => $this->moneda,
            'monto' => self::toNullableFloat($this->monto),
            'monto_convertido' => self::toNullableFloat($this->monto_convertido),
            'es_externa' => $this->es_externa,
            'banco_externo' => $this->banco_externo,
            'referencia' => $this->referencia,
            'cuenta_origen' => $this->cuenta_origen,
            'cuenta_destino' => $this->cuenta_destino,
            'registrado_por' => $this->registrado_por,
            'fecha_transaccion' => self::toIso8601($this->fecha_transaccion),
            'hora_transaccion' => self::toIso8601($this->hora_transaccion),
            'created_at' => self::toIso8601($this->created_at),
        ];
    }

    /**
     * MongoDB puede devolver fechas como UTCDateTime o documentos antiguos como string;
     * llamar ->toIso8601String() solo sobre Carbon rompe la respuesta JSON (500 / página en blanco).
     */
    private static function toIso8601(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        // BSON UTCDateTime (ext-mongodb): sin type-hint para no depender del stub del IDE.
        if (is_object($value) && method_exists($value, 'toDateTime')) {
            try {
                $dt = $value->toDateTime();

                return $dt instanceof DateTimeInterface
                    ? $dt->format(DateTimeInterface::ATOM)
                    : null;
            } catch (\Throwable) {
                return null;
            }
        }

        if (is_string($value)) {
            try {
                return (new DateTimeImmutable($value))->format(DateTimeInterface::ATOM);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    private static function toNullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_float($value) || is_int($value)) {
            return (float) $value;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            $s = trim((string) $value);

            return ($s !== '' && is_numeric($s)) ? (float) $s : null;
        }

        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        return is_numeric($value) ? (float) $value : null;
    }
}
