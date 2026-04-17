<?php

namespace App\Http\Controllers\Api\V1\Transaccion;

use App\Enums\EstadoTransaccion;
use App\Enums\Moneda;
use App\Enums\TipoTransaccion;
use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Http\Requests\Api\V1\Transaccion\StoreTransaccionRequest;
use App\Http\Resources\Api\V1\TransaccionResource;
use App\Models\Cuentas;
use App\Models\Transaccion;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TransaccionController extends Controller
{
    public function index(): JsonResponse
    {
        $transacciones = Transaccion::with(['cuentaOrigen', 'cuentaDestino'])->paginate(15);

        return ApiResponse::success(
            data: [
                'transacciones' => TransaccionResource::collection($transacciones->items()),
                'meta' => [
                    'current_page' => $transacciones->currentPage(),
                    'last_page'    => $transacciones->lastPage(),
                    'per_page'     => $transacciones->perPage(),
                    'total'        => $transacciones->total(),
                ],
            ],
            message: 'Transacciones obtenidas correctamente.'
        );
    }

    public function indexAll(): JsonResponse
    {
        $transacciones = Transaccion::with(['cuentaOrigen', 'cuentaDestino'])->get();

        return ApiResponse::success(
            data: TransaccionResource::collection($transacciones),
            message: 'Transacciones obtenidas correctamente.'
        );
    }

    public function show(int $id): JsonResponse
    {
        $transaccion = Transaccion::with(['cuentaOrigen', 'cuentaDestino'])->findOrFail($id);

        return ApiResponse::success(
            data: new TransaccionResource($transaccion),
            message: 'Transacción obtenida correctamente.'
        );
    }

    public function store(StoreTransaccionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $tipo = TipoTransaccion::from($data['tipo_transaccion']);

        try {
            $transaccion = DB::transaction(function () use ($data, $tipo) {
                $cuentaOrigen  = isset($data['id_cuenta_origen'])
                    ? Cuentas::lockForUpdate()->findOrFail($data['id_cuenta_origen'])
                    : null;

                $cuentaDestino = isset($data['id_cuenta_destino'])
                    ? Cuentas::lockForUpdate()->findOrFail($data['id_cuenta_destino'])
                    : null;

                $monedaTransaccion = Moneda::from($data['moneda']);
                $tipoCambio        = (float) env('TIPO_CAMBIO_USD_GTQ', 7.65);

                $montoEnOrigen  = $this->convertirMonto((float) $data['monto'], $monedaTransaccion, $cuentaOrigen?->moneda, $tipoCambio);
                $montoEnDestino = $this->convertirMonto((float) $data['monto'], $monedaTransaccion, $cuentaDestino?->moneda, $tipoCambio);

                if ($cuentaOrigen && (float) $cuentaOrigen->saldo < $montoEnOrigen) {
                    throw new \DomainException('Saldo insuficiente en la cuenta de origen.');
                }

                // monto_convertido refleja el monto debitado/acreditado en la moneda de la cuenta principal
                $data['monto_convertido'] = $cuentaOrigen ? $montoEnOrigen : $montoEnDestino;

                $transaccion = Transaccion::create($data);

                match ($tipo) {
                    TipoTransaccion::Transferencia => $this->aplicarTransferencia($cuentaOrigen, $cuentaDestino, $montoEnOrigen, $montoEnDestino),
                    TipoTransaccion::Deposito      => $this->aplicarDeposito($cuentaDestino, $montoEnDestino),
                    TipoTransaccion::Retiro        => $this->aplicarRetiro($cuentaOrigen, $montoEnOrigen),
                };

                $transaccion->update(['estado' => EstadoTransaccion::Completada->value]);

                return $transaccion->load(['cuentaOrigen', 'cuentaDestino']);
            });

            return ApiResponse::success(
                data: new TransaccionResource($transaccion),
                message: 'Transacción realizada correctamente.',
                status: 201
            );
        } catch (\DomainException $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                status: 422
            );
        }
    }

    private function convertirMonto(float $monto, Moneda $monedaOrigen, ?Moneda $monedaCuenta, float $tipoCambio): float
    {
        if ($monedaCuenta === null || $monedaOrigen === $monedaCuenta) {
            return $monto;
        }

        // Transacción en dólares, cuenta en quetzales → multiplicar
        if ($monedaOrigen === Moneda::Dolar && $monedaCuenta === Moneda::Quetzal) {
            return round($monto * $tipoCambio, 2);
        }

        // Transacción en quetzales, cuenta en dólares → dividir
        return round($monto / $tipoCambio, 2);
    }

    private function aplicarTransferencia(Cuentas $origen, Cuentas $destino, float $montoOrigen, float $montoDestino): void
    {
        $origen->decrement('saldo', $montoOrigen);
        $origen->decrement('saldo_disponible', $montoOrigen);
        $destino->increment('saldo', $montoDestino);
        $destino->increment('saldo_disponible', $montoDestino);
    }

    private function aplicarDeposito(Cuentas $destino, float $monto): void
    {
        $destino->increment('saldo', $monto);
        $destino->increment('saldo_disponible', $monto);
    }

    private function aplicarRetiro(Cuentas $origen, float $monto): void
    {
        $origen->decrement('saldo', $monto);
        $origen->decrement('saldo_disponible', $monto);
    }
}
