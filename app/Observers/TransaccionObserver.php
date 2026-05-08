<?php

namespace App\Observers;

use App\Models\Mongo\TransaccionSnapshot;
use App\Models\Transaccion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * TransaccionObserver
 * -------------------
 * Cada vez que una transacción se crea o cambia de estado, se persiste un
 * snapshot inmutable y desnormalizado en MongoDB. El snapshot incluye los
 * datos del cliente origen y destino, banco externo, etc., para que las
 * consultas históricas no requieran joins en MySQL.
 */
class TransaccionObserver
{
    public function created(Transaccion $transaccion): void
    {
        $this->guardarSnapshot($transaccion, 'creada');
    }

    public function updated(Transaccion $transaccion): void
    {
        if ($transaccion->wasChanged('estado')) {
            $this->guardarSnapshot($transaccion, 'estado_actualizado');
        }
    }

    private function guardarSnapshot(Transaccion $transaccion, string $motivo): void
    {
        try {
            $transaccion->loadMissing([
                'cuentaOrigen.cliente',
                'cuentaDestino.cliente',
                'transferenciaExterna',
            ]);

            $usuario = Auth::user();
            $request = request();

            TransaccionSnapshot::create([
                'transaccion_id_sql' => $transaccion->id,
                'motivo'             => $motivo,
                'tipo_transaccion'   => $transaccion->tipo_transaccion?->value,
                'estado'             => $transaccion->estado?->value,
                'moneda'             => $transaccion->moneda?->value,
                'monto'              => (float) $transaccion->monto,
                'monto_convertido'   => $transaccion->monto_convertido !== null
                    ? (float) $transaccion->monto_convertido
                    : null,
                'es_externa'         => (bool) $transaccion->es_externa,
                'banco_externo'      => $transaccion->banco_externo
                    ?? $transaccion->transferenciaExterna?->banco_externo,
                'referencia'         => $transaccion->referencia,
                'cuenta_origen'      => $this->resumirCuenta($transaccion->cuentaOrigen),
                'cuenta_destino'     => $this->resumirCuenta($transaccion->cuentaDestino),
                'registrado_por'     => $usuario ? [
                    'id'         => $usuario->getKey(),
                    'name'       => $usuario->name ?? null,
                    'email'      => $usuario->email ?? null,
                    'ip'         => $request?->ip(),
                    'user_agent' => $request?->userAgent(),
                ] : null,
                'fecha_transaccion'  => $transaccion->fecha_transaccion,
                'hora_transaccion'   => $transaccion->hora_transaccion,
                'created_at'         => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('No se pudo guardar TransaccionSnapshot en MongoDB', [
                'transaccion_id' => $transaccion->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resumirCuenta(mixed $cuenta): ?array
    {
        if (! $cuenta) {
            return null;
        }

        return [
            'id'             => $cuenta->id,
            'numero_cuenta'  => $cuenta->numero_cuenta,
            'tipo_cuenta'    => $cuenta->tipo_cuenta?->value ?? null,
            'moneda'         => $cuenta->moneda?->value ?? null,
            'cliente'        => $cuenta->cliente ? [
                'id'      => $cuenta->cliente->id,
                'nombres' => $cuenta->cliente->nombres ?? null,
                'apellidos' => $cuenta->cliente->apellidos ?? null,
                'dpi'     => $cuenta->cliente->dpi ?? null,
            ] : null,
        ];
    }
}
