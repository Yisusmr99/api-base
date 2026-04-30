<?php

namespace App\Http\Controllers\Api\V1\Reporte;

use App\Enums\EstadoTransaccion;
use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Http\Helpers\DateHelper;
use App\Http\Requests\Api\V1\Reporte\ActividadMensualRequest;
use App\Http\Requests\Api\V1\Reporte\HistorialTransaccionesRequest;
use App\Http\Requests\Api\V1\Reporte\ListadoCuentasRequest;
use App\Http\Requests\Api\V1\Reporte\TransferenciasExternasRequest;
use App\Http\Resources\Api\V1\ClienteResource;
use App\Http\Resources\Api\V1\CuentaResource;
use App\Http\Resources\Api\V1\TransaccionResource;
use App\Http\Resources\Api\V1\TransferenciaExternaResource;
use App\Models\Cliente;
use App\Models\Cuentas;
use App\Models\Transaccion;
use App\Models\TransferenciaExterna;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * ReporteController
 *
 * Endpoints para el módulo de Reportes:
 *  1. estadoCuenta:        Estado de cuenta detallado de un cliente.
 *  2. historialTransacciones: Historial de transacciones con filtros.
 *  3. listadoCuentas:      Listado de cuentas con saldos y filtros.
 *  4. transferenciasExternas: Transferencias externas en un período.
 *  5. actividadMensual:    Resumen estadístico mensual.
 *
 * Helper:
 *  - clientesLista: Lista compacta de clientes para selectores en el frontend.
 */
class ReporteController extends Controller
{
    /**
     * Lista compacta de clientes para selectores.
     * GET /reportes/clientes
     */
    public function clientesLista(): JsonResponse
    {
        try {
            $clientes = Cliente::withCount('cuentas')
                ->orderBy('nombres')
                ->get()
                ->map(fn ($c) => [
                    'id'               => $c->id,
                    'nombre_completo'  => trim($c->nombres . ' ' . $c->apellidos),
                    'dpi'              => $c->dpi,
                    'estado'           => $c->estado,
                    'cantidad_cuentas' => (int) $c->cuentas_count,
                ]);

            return ApiResponse::success(
                data: $clientes,
                message: 'Lista de clientes obtenida correctamente.'
            );
        } catch (\Throwable $th) {
            return ApiResponse::error(
                message: 'Error al obtener la lista de clientes: ' . $th->getMessage(),
                status: 500
            );
        }
    }

    /**
     * 1. Estado de cuenta detallado por cliente.
     * GET /reportes/estado-cuenta/{clienteId}
     */
    public function estadoCuenta(int $clienteId): JsonResponse
    {
        try {
            $cliente = Cliente::with('cuentas')->find($clienteId);

            if (!$cliente) {
                return ApiResponse::error(message: 'Cliente no encontrado.', status: 404);
            }

            $cuentaIds = $cliente->cuentas->pluck('id')->all();

            // Resumen de cuentas
            $cuentasActivas   = $cliente->cuentas->where('estado', true)->count();
            $cuentasInactivas = $cliente->cuentas->where('estado', false)->count();

            // Saldos por moneda en cuentas activas
            $saldosPropios = $cliente->cuentas
                ->where('estado', true)
                ->groupBy(fn ($c) => $c->moneda?->value ?? 'Q')
                ->map(fn ($grupo, $moneda) => [
                    'moneda'           => $moneda,
                    'cantidad_cuentas' => $grupo->count(),
                    'total_disponible' => (float) $grupo->sum('saldo_disponible'),
                    'total_saldo'      => (float) $grupo->sum('saldo'),
                ])
                ->values();

            // Distribución por tipo de cuenta
            $distribucionTipo = $cliente->cuentas
                ->groupBy(fn ($c) => $c->tipo_cuenta?->value ?? 'desconocido')
                ->map(fn ($g) => $g->count());

            // Movimientos externos del cliente
            $salientes = collect();
            $entrantes = collect();

            if (!empty($cuentaIds)) {
                $salientes = Transaccion::with('transferenciaExterna')
                    ->where('es_externa', true)
                    ->whereIn('id_cuenta_origen', $cuentaIds)
                    ->where('estado', EstadoTransaccion::Completada->value)
                    ->get();

                $entrantes = Transaccion::with('transferenciaExterna')
                    ->where('es_externa', true)
                    ->whereIn('id_cuenta_destino', $cuentaIds)
                    ->where('estado', EstadoTransaccion::Completada->value)
                    ->get();
            }

            $resumenSalientes = $salientes
                ->groupBy(fn ($t) => $t->banco_externo ?? ($t->transferenciaExterna->banco_externo ?? 'Desconocido'))
                ->map(fn ($grupo, $banco) => [
                    'banco_externo' => $banco,
                    'cantidad'      => $grupo->count(),
                    'monto_total'   => (float) $grupo->sum('monto'),
                ])
                ->values();

            $resumenEntrantes = $entrantes
                ->groupBy(fn ($t) => $t->banco_externo ?? ($t->transferenciaExterna->banco_externo ?? 'Desconocido'))
                ->map(fn ($grupo, $banco) => [
                    'banco_externo' => $banco,
                    'cantidad'      => $grupo->count(),
                    'monto_total'   => (float) $grupo->sum('monto'),
                ])
                ->values();

            $totalEnviadoExterno  = (float) $salientes->sum('monto');
            $totalRecibidoExterno = (float) $entrantes->sum('monto');

            // Transacciones recientes (últimas 20 para el reporte)
            $transaccionesRecientes = collect();
            if (!empty($cuentaIds)) {
                $transaccionesRecientes = Transaccion::with([
                        'cuentaOrigen',
                        'cuentaDestino',
                        'transferenciaExterna',
                    ])
                    ->where(function ($q) use ($cuentaIds) {
                        $q->whereIn('id_cuenta_origen', $cuentaIds)
                          ->orWhereIn('id_cuenta_destino', $cuentaIds);
                    })
                    ->orderByDesc('created_at')
                    ->limit(20)
                    ->get();
            }

            $ultimaActividad = $transaccionesRecientes->first()?->created_at?->toIso8601String();

            return ApiResponse::success(
                data: [
                    'cliente' => new ClienteResource($cliente),
                    'cuentas' => [
                        'total'             => $cliente->cuentas->count(),
                        'activas'           => $cuentasActivas,
                        'inactivas'         => $cuentasInactivas,
                        'distribucion_tipo' => $distribucionTipo,
                        'detalle'           => CuentaResource::collection($cliente->cuentas),
                    ],
                    'saldos_propios'       => $saldosPropios,
                    'movimientos_externos' => [
                        'total_enviado'        => $totalEnviadoExterno,
                        'total_recibido'       => $totalRecibidoExterno,
                        'salientes_por_banco'  => $resumenSalientes,
                        'entrantes_por_banco'  => $resumenEntrantes,
                    ],
                    'transacciones_recientes' => TransaccionResource::collection($transaccionesRecientes),
                    'ultima_actividad'        => $ultimaActividad,
                    'fecha_emision'           => DateHelper::now()->toIso8601String(),
                ],
                message: 'Estado de cuenta generado correctamente.'
            );
        } catch (\Throwable $th) {
            return ApiResponse::error(
                message: 'Error al generar el estado de cuenta: ' . $th->getMessage(),
                status: 500
            );
        }
    }

    /**
     * 2. Historial de transacciones con filtros.
     * GET /reportes/transacciones?desde=&hasta=&cliente_id=&cuenta_id=&tipo=&estado=&es_externa=
     */
    public function historialTransacciones(HistorialTransaccionesRequest $request): JsonResponse
    {
        try {
            $filtros = $request->validated();

            $query = Transaccion::with(['cuentaOrigen.cliente', 'cuentaDestino.cliente', 'transferenciaExterna']);

            if (!empty($filtros['desde'])) {
                $query->whereDate('created_at', '>=', $filtros['desde']);
            }
            if (!empty($filtros['hasta'])) {
                $query->whereDate('created_at', '<=', $filtros['hasta']);
            }
            if (!empty($filtros['tipo'])) {
                $query->where('tipo_transaccion', $filtros['tipo']);
            }
            if (!empty($filtros['estado'])) {
                $query->where('estado', $filtros['estado']);
            }
            if (isset($filtros['es_externa'])) {
                $query->where('es_externa', (bool) $filtros['es_externa']);
            }
            if (!empty($filtros['cuenta_id'])) {
                $cuentaId = (int) $filtros['cuenta_id'];
                $query->where(function ($q) use ($cuentaId) {
                    $q->where('id_cuenta_origen', $cuentaId)
                      ->orWhere('id_cuenta_destino', $cuentaId);
                });
            }
            if (!empty($filtros['cliente_id'])) {
                $cuentaIds = Cuentas::where('id_cliente', $filtros['cliente_id'])->pluck('id')->all();
                $query->where(function ($q) use ($cuentaIds) {
                    $q->whereIn('id_cuenta_origen', $cuentaIds)
                      ->orWhereIn('id_cuenta_destino', $cuentaIds);
                });
            }

            $transacciones = $query->orderByDesc('created_at')->limit(500)->get();

            // Totales
            $totalRegistros = $transacciones->count();
            $totalCompletadas = $transacciones->where('estado', EstadoTransaccion::Completada->value)->count();
            $montoTotal = (float) $transacciones->where('estado', EstadoTransaccion::Completada->value)->sum('monto');

            $totalesPorTipo = $transacciones
                ->groupBy(fn ($t) => $t->tipo_transaccion?->value ?? 'desconocido')
                ->map(fn ($g) => [
                    'cantidad'    => $g->count(),
                    'monto_total' => (float) $g->where('estado', EstadoTransaccion::Completada->value)->sum('monto'),
                ]);

            return ApiResponse::success(
                data: [
                    'filtros'         => $filtros,
                    'totales'         => [
                        'registros'      => $totalRegistros,
                        'completadas'    => $totalCompletadas,
                        'monto_total'    => $montoTotal,
                        'por_tipo'       => $totalesPorTipo,
                    ],
                    'transacciones'   => TransaccionResource::collection($transacciones),
                    'fecha_emision'   => DateHelper::now()->toIso8601String(),
                ],
                message: 'Historial de transacciones generado correctamente.'
            );
        } catch (\Throwable $th) {
            return ApiResponse::error(
                message: 'Error al generar el historial: ' . $th->getMessage(),
                status: 500
            );
        }
    }

    /**
     * 3. Listado de cuentas con filtros.
     * GET /reportes/cuentas?cliente_id=&tipo_cuenta=&moneda=&estado=
     */
    public function listadoCuentas(ListadoCuentasRequest $request): JsonResponse
    {
        try {
            $filtros = $request->validated();

            $query = Cuentas::with('cliente');

            if (!empty($filtros['cliente_id'])) {
                $query->where('id_cliente', $filtros['cliente_id']);
            }
            if (!empty($filtros['tipo_cuenta'])) {
                $query->where('tipo_cuenta', $filtros['tipo_cuenta']);
            }
            if (!empty($filtros['moneda'])) {
                $query->where('moneda', $filtros['moneda']);
            }
            if (isset($filtros['estado'])) {
                $query->where('estado', (bool) $filtros['estado']);
            }

            $cuentas = $query->orderBy('id_cliente')->orderBy('numero_cuenta')->get();

            // Totales por moneda
            $totalesPorMoneda = $cuentas
                ->groupBy(fn ($c) => $c->moneda?->value ?? 'Q')
                ->map(fn ($g, $moneda) => [
                    'moneda'           => $moneda,
                    'cantidad_cuentas' => $g->count(),
                    'total_disponible' => (float) $g->sum('saldo_disponible'),
                    'total_saldo'      => (float) $g->sum('saldo'),
                ])
                ->values();

            $totalesPorTipo = $cuentas
                ->groupBy(fn ($c) => $c->tipo_cuenta?->value ?? 'desconocido')
                ->map(fn ($g) => $g->count());

            return ApiResponse::success(
                data: [
                    'filtros'          => $filtros,
                    'totales'          => [
                        'cantidad'        => $cuentas->count(),
                        'activas'         => $cuentas->where('estado', true)->count(),
                        'inactivas'       => $cuentas->where('estado', false)->count(),
                        'por_moneda'      => $totalesPorMoneda,
                        'por_tipo'        => $totalesPorTipo,
                    ],
                    'cuentas'          => CuentaResource::collection($cuentas),
                    'fecha_emision'    => DateHelper::now()->toIso8601String(),
                ],
                message: 'Listado de cuentas generado correctamente.'
            );
        } catch (\Throwable $th) {
            return ApiResponse::error(
                message: 'Error al generar el listado: ' . $th->getMessage(),
                status: 500
            );
        }
    }

    /**
     * 4. Reporte de transferencias externas.
     * GET /reportes/transferencias-externas?desde=&hasta=&banco_externo=&tipo=
     */
    public function transferenciasExternas(TransferenciasExternasRequest $request): JsonResponse
    {
        try {
            $filtros = $request->validated();

            $query = TransferenciaExterna::with('transaccion.cuentaOrigen.cliente', 'transaccion.cuentaDestino.cliente');

            if (!empty($filtros['desde'])) {
                $query->whereDate('fecha_envio', '>=', $filtros['desde']);
            }
            if (!empty($filtros['hasta'])) {
                $query->whereDate('fecha_envio', '<=', $filtros['hasta']);
            }
            if (!empty($filtros['banco_externo'])) {
                $query->where('banco_externo', 'like', '%' . $filtros['banco_externo'] . '%');
            }
            if (!empty($filtros['tipo'])) {
                $query->where('tipo', $filtros['tipo']);
            }

            $transferencias = $query->orderByDesc('fecha_envio')->limit(500)->get();

            // Resumen por banco
            $porBanco = $transferencias
                ->groupBy('banco_externo')
                ->map(fn ($g, $banco) => [
                    'banco_externo' => $banco,
                    'cantidad'      => $g->count(),
                    'monto_total'   => (float) $g->sum(fn ($t) => $t->transaccion?->monto ?? 0),
                ])
                ->values();

            // Resumen por tipo
            $porTipo = $transferencias
                ->groupBy(fn ($t) => $t->tipo?->value ?? 'desconocido')
                ->map(fn ($g) => [
                    'cantidad'    => $g->count(),
                    'monto_total' => (float) $g->sum(fn ($t) => $t->transaccion?->monto ?? 0),
                ]);

            $montoTotal = (float) $transferencias->sum(fn ($t) => $t->transaccion?->monto ?? 0);

            return ApiResponse::success(
                data: [
                    'filtros'        => $filtros,
                    'totales'        => [
                        'cantidad'    => $transferencias->count(),
                        'monto_total' => $montoTotal,
                        'por_banco'   => $porBanco,
                        'por_tipo'    => $porTipo,
                    ],
                    'transferencias' => TransferenciaExternaResource::collection($transferencias),
                    'fecha_emision'  => DateHelper::now()->toIso8601String(),
                ],
                message: 'Reporte de transferencias externas generado correctamente.'
            );
        } catch (\Throwable $th) {
            return ApiResponse::error(
                message: 'Error al generar el reporte: ' . $th->getMessage(),
                status: 500
            );
        }
    }

    /**
     * 5. Resumen de actividad mensual.
     * GET /reportes/actividad-mensual?anio=&mes=
     */
    public function actividadMensual(ActividadMensualRequest $request): JsonResponse
    {
        try {
            $filtros = $request->validated();
            $now = DateHelper::now();

            $anio = (int) ($filtros['anio'] ?? $now->year);
            $mes  = (int) ($filtros['mes']  ?? $now->month);

            $inicio = $now->copy()->setDate($anio, $mes, 1)->startOfMonth();
            $fin    = $inicio->copy()->endOfMonth();

            // ---- Transacciones del período ----
            $transaccionesPeriodo = Transaccion::whereBetween('created_at', [$inicio, $fin])->get();

            $totalTransacciones        = $transaccionesPeriodo->count();
            $transaccionesCompletadas  = $transaccionesPeriodo->where('estado', EstadoTransaccion::Completada->value)->count();
            $transaccionesPendientes   = $transaccionesPeriodo->where('estado', EstadoTransaccion::Pendiente->value)->count();
            $transaccionesFallidas     = $transaccionesPeriodo->where('estado', EstadoTransaccion::Fallida->value)->count();

            $montoMovido = (float) $transaccionesPeriodo
                ->where('estado', EstadoTransaccion::Completada->value)
                ->sum('monto');

            $porTipo = $transaccionesPeriodo
                ->groupBy(fn ($t) => $t->tipo_transaccion?->value ?? 'desconocido')
                ->map(fn ($g) => [
                    'cantidad'    => $g->count(),
                    'monto_total' => (float) $g->where('estado', EstadoTransaccion::Completada->value)->sum('monto'),
                ]);

            $porMoneda = $transaccionesPeriodo
                ->where('estado', EstadoTransaccion::Completada->value)
                ->groupBy(fn ($t) => $t->moneda?->value ?? 'Q')
                ->map(fn ($g) => [
                    'cantidad'    => $g->count(),
                    'monto_total' => (float) $g->sum('monto'),
                ]);

            // ---- Top 5 clientes con más transacciones ----
            $topClientes = DB::table('transacciones as t')
                ->leftJoin('cuentas as co', 'co.id', '=', 't.id_cuenta_origen')
                ->leftJoin('cuentas as cd', 'cd.id', '=', 't.id_cuenta_destino')
                ->leftJoin('clientes as cl', function ($join) {
                    $join->on('cl.id', '=', 'co.id_cliente')
                         ->orOn('cl.id', '=', 'cd.id_cliente');
                })
                ->whereBetween('t.created_at', [$inicio, $fin])
                ->whereNotNull('cl.id')
                ->select(
                    'cl.id as cliente_id',
                    DB::raw("CONCAT(cl.nombres, ' ', cl.apellidos) as nombre_completo"),
                    DB::raw('COUNT(t.id) as cantidad_transacciones'),
                    DB::raw('SUM(CASE WHEN t.estado = "completada" THEN t.monto ELSE 0 END) as monto_total')
                )
                ->groupBy('cl.id', 'cl.nombres', 'cl.apellidos')
                ->orderByDesc('cantidad_transacciones')
                ->limit(5)
                ->get()
                ->map(fn ($row) => [
                    'cliente_id'             => (int) $row->cliente_id,
                    'nombre_completo'        => $row->nombre_completo,
                    'cantidad_transacciones' => (int) $row->cantidad_transacciones,
                    'monto_total'            => (float) $row->monto_total,
                ]);

            // ---- Transferencias externas del período ----
            $transferenciasExternasPeriodo = TransferenciaExterna::whereBetween('fecha_envio', [$inicio, $fin])->count();

            // ---- Cuentas creadas en el período ----
            $cuentasNuevas = Cuentas::whereBetween('created_at', [$inicio, $fin])->count();

            // ---- Clientes nuevos en el período ----
            $clientesNuevos = Cliente::whereBetween('created_at', [$inicio, $fin])->count();

            return ApiResponse::success(
                data: [
                    'periodo' => [
                        'anio'           => $anio,
                        'mes'            => $mes,
                        'fecha_inicio'   => $inicio->toIso8601String(),
                        'fecha_fin'      => $fin->toIso8601String(),
                    ],
                    'transacciones' => [
                        'total'        => $totalTransacciones,
                        'completadas'  => $transaccionesCompletadas,
                        'pendientes'   => $transaccionesPendientes,
                        'fallidas'     => $transaccionesFallidas,
                        'monto_movido' => $montoMovido,
                        'por_tipo'     => $porTipo,
                        'por_moneda'   => $porMoneda,
                    ],
                    'transferencias_externas' => $transferenciasExternasPeriodo,
                    'cuentas_nuevas'          => $cuentasNuevas,
                    'clientes_nuevos'         => $clientesNuevos,
                    'top_clientes'            => $topClientes,
                    'fecha_emision'           => DateHelper::now()->toIso8601String(),
                ],
                message: 'Reporte de actividad mensual generado correctamente.'
            );
        } catch (\Throwable $th) {
            return ApiResponse::error(
                message: 'Error al generar el reporte mensual: ' . $th->getMessage(),
                status: 500
            );
        }
    }
}
