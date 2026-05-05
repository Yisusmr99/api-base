<?php

namespace App\Services\BancosExternos;

use App\Services\BancosExternos\Contracts\BancoExternoContract;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ExclusitBankService implements BancoExternoContract
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey
    ) {}

    public function verificarCuenta(string $numeroCuenta): array
    {
        $response = Http::withHeaders(['X-API-Key' => $this->apiKey])
            ->get("{$this->baseUrl}/cuenta/verificar", ['numero' => $numeroCuenta]);

        if ($response->serverError()) {
            throw new RuntimeException('ExclusitBank no disponible. Intente más tarde.');
        }

        $data = $response->json();

        if (!($data['ok'] ?? false)) {
            throw new RuntimeException($data['error'] ?? 'Cuenta no encontrada en ExclusitBank.');
        }

        return $data['cuenta'];
    }

    public function enviarTransferencia(
        string $referencia,
        string $numeroCuentaDestino,
        float $monto,
        ?string $descripcion = null
    ): array {
        $payload = [
            'referencia'            => $referencia,
            'numero_cuenta_destino' => $numeroCuentaDestino,
            'monto'                 => $monto,
        ];

        if ($descripcion !== null) {
            $payload['descripcion'] = $descripcion;
        }

        $response = Http::withHeaders(['X-API-Key' => $this->apiKey])
            ->post("{$this->baseUrl}/transferencia/recibir", $payload);

        if ($response->serverError()) {
            throw new RuntimeException('ExclusitBank no disponible al procesar la transferencia.');
        }

        $data = $response->json();

        if (!($data['ok'] ?? false)) {
            throw new RuntimeException($data['error'] ?? 'Transferencia rechazada por ExclusitBank.');
        }

        return $data['detalle'] ?? [];
    }
}
