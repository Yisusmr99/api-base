<?php

namespace App\Services\BancosExternos;

use App\Services\BancosExternos\Contracts\BancoExternoContract;
use InvalidArgumentException;

/**
 * =============================================================================
 * GUÍA PARA INTEGRAR UN NUEVO BANCO EXTERNO
 * =============================================================================
 *
 * PASO 1 — Variables de entorno (.env)
 * ------------------------------------
 * Agrega las credenciales del nuevo banco:
 *
 *   BASE_URL_NUEVO_BANCO=https://api.nuevobanco.com
 *   API_KEY_NUEVO_BANCO=tu-api-key-aqui
 *
 *
 * PASO 2 — Crear el Service
 * --------------------------
 * Crea app/Services/BancosExternos/NuevoBancoService.php implementando
 * BancoExternoContract. Debe tener dos métodos:
 *
 *   verificarCuenta(string $numeroCuenta): array
 *     → Consulta el endpoint del banco para validar que la cuenta existe
 *       y está activa. Lanza RuntimeException si no existe o hay error de red.
 *     → Retorna un array con los datos de la cuenta (número, titular, etc.).
 *
 *   enviarTransferencia(string $referencia, string $numeroCuentaDestino,
 *                       float $monto, ?string $descripcion): array
 *     → Llama al endpoint del banco para acreditar fondos en la cuenta destino.
 *     → El monto SIEMPRE debe enviarse en la moneda que acepte ese banco
 *       (ExclusitBank acepta solo GTQ; otros pueden aceptar USD).
 *     → Lanza RuntimeException si el banco rechaza o hay error de red.
 *     → Retorna el detalle de confirmación que devuelva el banco.
 *
 *
 * PASO 3 — Registrar el banco en este Factory
 * --------------------------------------------
 * a) Agrega todas las variantes del nombre en $map:
 *
 *   'nuevo banco'  => NuevoBancoService::class,
 *   'nuevobanco'   => NuevoBancoService::class,
 *
 * b) Agrega el case en el match de make():
 *
 *   NuevoBancoService::class => new NuevoBancoService(
 *       baseUrl: env('BASE_URL_NUEVO_BANCO'),
 *       apiKey:  env('API_KEY_NUEVO_BANCO')
 *   ),
 *
 *
 * PASO 4 — Probar
 * ---------------
 * Usa el endpoint POST /api/v1/transferencias-externas con tipo=saliente
 * y banco_externo igual a uno de los nombres registrados en $map.
 * El sistema verificará la cuenta primero y luego ejecutará la transferencia.
 *
 * =============================================================================
 */
class BancoExternoFactory
{
    /**
     * Nombres reconocidos (en minúsculas) mapeados a su clase de servicio.
     */
    private static array $map = [
        'exclousitbank'  => ExclusitBankService::class,
        'exclousit bank' => ExclusitBankService::class,
        'exclousit'      => ExclusitBankService::class,
    ];

    public static function make(string $bancoNombre): BancoExternoContract
    {
        $key          = strtolower(trim($bancoNombre));
        $serviceClass = self::$map[$key] ?? null;

        if ($serviceClass === null) {
            throw new InvalidArgumentException("Banco externo no soportado: {$bancoNombre}");
        }

        return match ($serviceClass) {
            ExclusitBankService::class => new ExclusitBankService(
                baseUrl: env('BASE_URL_EXCLOUSIT'),
                apiKey:  env('API_KEY_EXCLOUSIT')
            ),
            default => throw new InvalidArgumentException("Sin configuración para: {$bancoNombre}"),
        };
    }

    public static function soportado(string $bancoNombre): bool
    {
        return isset(self::$map[strtolower(trim($bancoNombre))]);
    }
}
