<?php

namespace App\Http\Controllers\Api\V1\TransferenciaExterna;

use App\Enums\EstadoTransaccion;
use App\Enums\Moneda;
use App\Enums\TipoTransaccion;
use App\Enums\TipoTransferenciaExterna;
use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Http\Requests\Api\V1\TransferenciaExterna\StoreTransferenciaExternaRequest;
use App\Http\Resources\Api\V1\TransferenciaExternaResource;
use App\Models\Cuentas;
use App\Models\Transaccion;
use App\Models\TransferenciaExterna;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TransferenciaExternaController extends Controller
{
    public function index(): JsonResponse
    {
        $transferencias = TransferenciaExterna::with(['transaccion.cuentaOrigen', 'transaccion.cuentaDestino'])
            ->paginate(15);

        return ApiResponse::success(
            data: [
                'transferencias_externas' => TransferenciaExternaResource::collection($transferencias->items()),
                'meta' => [
                    'current_page' => $transferencias->currentPage(),
                    'last_page'    => $transferencias->lastPage(),
                    'per_page'     => $transferencias->perPage(),
                    'total'        => $transferencias->total(),
                ],
            ],
            message: 'Transferencias externas obtenidas correctamente.'
        );
    }

    public function indexAll(): JsonResponse
    {
        $transferencias = TransferenciaExterna::with(['transaccion.cuentaOrigen', 'transaccion.cuentaDestino'])
            ->get();

        return ApiResponse::success(
            data: TransferenciaExternaResource::collection($transferencias),
            message: 'Transferencias externas obtenidas correctamente.'
        );
    }

    public function show(int $id): JsonResponse
    {
        $transferencia = TransferenciaExterna::with(['transaccion.cuentaOrigen', 'transaccion.cuentaDestino'])
            ->findOrFail($id);

        return ApiResponse::success(
            data: new TransferenciaExternaResource($transferencia),
            message: 'Transferencia externa obtenida correctamente.'
        );
    }

    public function store(StoreTransferenciaExternaRequest $request): JsonResponse
    {
        $data = $request->validated();
        $tipo = TipoTransferenciaExterna::from($data['tipo']);
        // dd($data);

        try {
            $transferenciaExterna = DB::transaction(function () use ($data, $tipo) {
                $now               = now();
                $monto             = (float) $data['monto'];
                $monedaTransaccion = Moneda::from($data['moneda']);
                $tipoCambio        = (float) env('TIPO_CAMBIO_USD_GTQ', 7.65);

                if ($tipo === TipoTransferenciaExterna::Entrante) {
                    $cuenta          = Cuentas::lockForUpdate()->findOrFail($data['id_cuenta_destino']);
                    $montoConvertido = $this->convertirMonto($monto, $monedaTransaccion, $cuenta->moneda, $tipoCambio);

                    $transaccion = Transaccion::create([
                        'tipo_transaccion'  => TipoTransaccion::Deposito->value,
                        'id_cuenta_destino' => $cuenta->id,
                        'monto'             => $monto,
                        'moneda'            => $data['moneda'],
                        'monto_convertido'  => $montoConvertido,
                        'referencia'        => $data['referencia'] ?? null,
                        'es_externa'        => true,
                        'banco_externo'     => $data['banco_externo'],
                        'estado'            => EstadoTransaccion::Completada->value,
                        'fecha_transaccion' => $now,
                        'hora_transaccion'  => $now,
                    ]);

                    $cuenta->increment('saldo', $montoConvertido);
                    $cuenta->increment('saldo_disponible', $montoConvertido);
                } else {
                    $cuenta          = Cuentas::lockForUpdate()->findOrFail($data['id_cuenta_origen']);
                    $montoConvertido = $this->convertirMonto($monto, $monedaTransaccion, $cuenta->moneda, $tipoCambio);

                    if ((float) $cuenta->saldo < $montoConvertido) {
                        throw new \DomainException('Saldo insuficiente en la cuenta de origen.');
                    }

                    $transaccion = Transaccion::create([
                        'tipo_transaccion'  => TipoTransaccion::Retiro->value,
                        'id_cuenta_origen'  => $cuenta->id,
                        'monto'             => $monto,
                        'moneda'            => $data['moneda'],
                        'monto_convertido'  => $montoConvertido,
                        'referencia'        => $data['referencia'] ?? null,
                        'es_externa'        => true,
                        'banco_externo'     => $data['banco_externo'],
                        'estado'            => EstadoTransaccion::Completada->value,
                        'fecha_transaccion' => $now,
                        'hora_transaccion'  => $now,
                    ]);

                    $cuenta->decrement('saldo', $montoConvertido);
                    $cuenta->decrement('saldo_disponible', $montoConvertido);
                }

                $transferenciaExterna = TransferenciaExterna::create([
                    'id_transaccion'      => $transaccion->id,
                    'banco_externo'       => $data['banco_externo'],
                    'cuenta_externa'      => $data['cuenta_externa'],
                    'codigo_confirmacion' => $data['codigo_confirmacion'],
                    'tipo'                => $data['tipo'],
                    'estado'              => 'confirmada',
                    'fecha_envio'         => $now,
                    'fecha_confirmacion'  => $now,
                ]);

                // TODO: Ver si es necesario recargar la transferencia externa con las relaciones para devolverla en la respuesta
                // return $transferenciaExterna->load(['transaccion.cuentaOrigen', 'transaccion.cuentaDestino']);
                return $transferenciaExterna->load(['transaccion']);
            });

            return ApiResponse::success(
                data: new TransferenciaExternaResource($transferenciaExterna),
                message: 'Transferencia externa registrada correctamente.',
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

        if ($monedaOrigen === Moneda::Dolar && $monedaCuenta === Moneda::Quetzal) {
            return round($monto * $tipoCambio, 2);
        }

        return round($monto / $tipoCambio, 2);
    }
}
