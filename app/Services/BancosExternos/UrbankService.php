<?php

namespace App\Services\BancosExternos;

use App\Services\BancosExternos\Contracts\BancoExternoContract;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class UrbankService implements BancoExternoContract
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly int    $idBancoOrigen
    ) {}

    public function verificarCuenta(string $numeroCuenta): array
    {
        $cuenta = $this->buscarCuenta($numeroCuenta);
        \Log::info("URBANK - Buscar cuenta destino: {$numeroCuenta}", ['cuenta' => $cuenta]);
        if ($cuenta === null) {
            throw new RuntimeException("Cuenta {$numeroCuenta} no encontrada en URBANK.");
        }

        return $cuenta;
    }

    public function enviarTransferencia(
        string  $referencia,
        string  $numeroCuentaDestino,
        float   $monto,
        ?string $descripcion = null,
        ?string $cuentaOrigen = null
    ): array {
        $cuenta = $this->buscarCuenta($numeroCuentaDestino);

        if ($cuenta === null) {
            throw new RuntimeException("Cuenta destino {$numeroCuentaDestino} no encontrada en URBANK.");
        }

        $payload = [
            'id_cuenta_destino'         => $cuenta['id_cuenta'],
            'monto'                     => $monto,
            'id_banco_origen'           => $this->idBancoOrigen,
            'cuenta_origen_externa'     => $cuentaOrigen,
            'codigo_referencia_externa' => $referencia,
        ];

        $response = Http::post("{$this->baseUrl}/transacciones/transferencia-entrante", $payload);

        if ($response->serverError()) {
            \Log::error("URBANK - Error en la transferencia:", ['payload' => $payload]);
            throw new RuntimeException('URBANK no disponible al procesar la transferencia.');
        }

        if (!$response->successful()) {
            $data = $response->json();
            \Log::warning("URBANK - Transferencia rechazada:", ['payload' => $payload, 'response' => $data]);
            throw new RuntimeException($data['message'] ?? $data['error'] ?? 'Transferencia rechazada por URBANK.');
        }

        return $response->json() ?? [];
    }

    private function buscarCuenta(string $numeroCuenta): ?array
    {
        $response = Http::get("{$this->baseUrl}/public/cuentas-disponibles");

        if ($response->serverError()) {
            throw new RuntimeException('URBANK no disponible. Intente más tarde.');
        }

        $body = $response->json();
        \Log::info("URBANK - Cuentas disponibles:", ['cuentas' => $body]);
        $lista = $body['cuentas'] ?? $body['data'] ?? [];

        return collect($lista)->first(
            fn($item) => ($item['numero_cuenta'] ?? $item['numero'] ?? '') === $numeroCuenta
        );
    }
}
