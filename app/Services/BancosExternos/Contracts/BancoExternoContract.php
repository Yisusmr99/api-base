<?php

namespace App\Services\BancosExternos\Contracts;

interface BancoExternoContract
{
    /**
     * Verifica que la cuenta exista y esté activa en el banco externo.
     * Lanza RuntimeException si la cuenta no existe o el banco no responde.
     *
     * @return array{numero: string, tipo: string, titular: string, estado: string}
     */
    public function verificarCuenta(string $numeroCuenta): array;

    /**
     * Envía fondos a la cuenta destino en el banco externo.
     * Lanza RuntimeException si el banco rechaza la transferencia.
     */
    public function enviarTransferencia(
        string $referencia,
        string $numeroCuentaDestino,
        float $monto,
        ?string $descripcion = null
    ): array;
}
