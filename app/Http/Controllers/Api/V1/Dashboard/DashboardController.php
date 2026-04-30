<?php

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Enums\EstadoTransaccion;
use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Http\Helpers\DateHelper;
use App\Models\Cliente;
use App\Models\Cuentas;
use App\Models\Transaccion;
use App\Models\TransferenciaExterna;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * DashboardController
 *
 * Endpoints agregados para el Dashboard:
 *  - summary: KPIs globales del sistema (cuentas, clientes, saldos, transacciones).
 *
 * NOTA: La consulta detallada por cliente se trasladó al módulo de Reportes
 * (App\Http\Controllers\Api\V1\Reporte\ReporteController).
 */
class DashboardController extends Controller
{
    /**
     * Resumen global del sistema.
     * KPIs de cuentas, clientes, saldos por moneda, distribución por tipo de cuenta y actividad reciente.
     */
    public function summary(): JsonResponse
    {
        try {
            // ---- Clientes ----
            $totalClientes      = Cliente::count();
            $clientesActivos    = Cliente::where('estado', true)->count();
            $clientesInactivos  = $totalClientes - $clientesActivos;

            // ---- Cuentas ----
            $totalCuentas      = Cuentas::count();
            $cuentasActivas    = Cuentas::where('estado', true)->count();
            $cuentasInactivas  = $totalCuentas - $cuentasActivas;

            // Distribución por tipo de cuenta
            $distribucionTipo = Cuentas::select('tipo_cuenta', DB::raw('count(*) as total'))
                ->groupBy('tipo_cuenta')
                ->pluck('total', 'tipo_cuenta')
                ->toArray();

            // Distribución por moneda (saldo_disponible)
            $saldosPorMoneda = Cuentas::select(
                    'moneda',
                    DB::raw('SUM(saldo_disponible) as total_disponible'),
                    DB::raw('SUM(saldo) as total_saldo'),
                    DB::raw('COUNT(*) as cantidad_cuentas')
                )
                ->where('estado', true)
                ->groupBy('moneda')
                ->get()
                ->keyBy('moneda')
                ->map(fn ($row) => [
                    'moneda'           => $row->moneda,
                    'cantidad_cuentas' => (int) $row->cantidad_cuentas,
                    'total_disponible' => (float) $row->total_disponible,
                    'total_saldo'      => (float) $row->total_saldo,
                ])
                ->values();

            // ---- Transacciones del mes en curso ----
            $inicioMes = DateHelper::now()->startOfMonth();
            $finMes    = DateHelper::now()->endOfMonth();

            $transaccionesMes = Transaccion::whereBetween('created_at', [$inicioMes, $finMes])->count();

            $montoMovidoMes = Transaccion::whereBetween('created_at', [$inicioMes, $finMes])
                ->where('estado', EstadoTransaccion::Completada->value)
                ->sum('monto');

            $transaccionesExternasMes = Transaccion::whereBetween('created_at', [$inicioMes, $finMes])
                ->where('es_externa', true)
                ->count();

            // Distribución por tipo de transacción del mes
            $distribucionTransacciones = Transaccion::select('tipo_transaccion', DB::raw('count(*) as total'))
                ->whereBetween('created_at', [$inicioMes, $finMes])
                ->groupBy('tipo_transaccion')
                ->pluck('total', 'tipo_transaccion')
                ->toArray();

            // ---- Transferencias externas (totales históricos) ----
            $totalTransferenciasExternas = TransferenciaExterna::count();

            return ApiResponse::success(
                data: [
                    'clientes' => [
                        'total'     => $totalClientes,
                        'activos'   => $clientesActivos,
                        'inactivos' => $clientesInactivos,
                    ],
                    'cuentas' => [
                        'total'              => $totalCuentas,
                        'activas'            => $cuentasActivas,
                        'inactivas'          => $cuentasInactivas,
                        'distribucion_tipo'  => $distribucionTipo,
                    ],
                    'saldos_por_moneda' => $saldosPorMoneda,
                    'transacciones_mes' => [
                        'periodo_inicio'         => $inicioMes->toIso8601String(),
                        'periodo_fin'            => $finMes->toIso8601String(),
                        'total'                  => $transaccionesMes,
                        'externas'               => $transaccionesExternasMes,
                        'monto_movido'           => (float) $montoMovidoMes,
                        'distribucion_por_tipo'  => $distribucionTransacciones,
                    ],
                    'transferencias_externas' => [
                        'total_historico' => $totalTransferenciasExternas,
                    ],
                ],
                message: 'Resumen del dashboard obtenido correctamente.'
            );
        } catch (\Throwable $th) {
            return ApiResponse::error(
                message: 'Error al obtener el resumen del dashboard: ' . $th->getMessage(),
                status: 500
            );
        }
    }
}
