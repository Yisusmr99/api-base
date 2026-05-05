<?php

namespace App\Http\Controllers\Api\V1\Auditoria;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Http\Requests\Api\V1\Auditoria\BuscarAuditoriaRequest;
use App\Http\Requests\Api\V1\Auditoria\BuscarSnapshotsRequest;
use App\Http\Resources\Api\V1\Auditoria\AuditLogResource;
use App\Http\Resources\Api\V1\Auditoria\TransaccionSnapshotResource;
use App\Models\Mongo\AuditLog;
use App\Models\Mongo\TransaccionSnapshot;
use Illuminate\Http\JsonResponse;

/**
 * AuditoriaController
 * -------------------
 * Endpoints de consulta sobre la base de datos NoSQL (MongoDB).
 *
 *  - GET /auditoria                 Bitácora de eventos con filtros y paginación.
 *  - GET /auditoria/{id}            Detalle de un evento.
 *  - GET /auditoria/resumen         Estadísticas agregadas (eventos por módulo,
 *                                   por día, por severidad).
 *  - GET /auditoria/snapshots       Snapshots de transacciones con filtros.
 *  - GET /auditoria/snapshots/{id}  Detalle de un snapshot.
 */
class AuditoriaController extends Controller
{
    /**
     * Búsqueda y filtrado de la bitácora de auditoría.
     */
    public function index(BuscarAuditoriaRequest $request): JsonResponse
    {
        try {
            $filtros = $request->validated();
            $perPage = (int) ($filtros['per_page'] ?? 25);

            $query = AuditLog::query();

            if (!empty($filtros['modulo'])) {
                $query->where('modulo', $filtros['modulo']);
            }
            if (!empty($filtros['accion'])) {
                $query->where('accion', $filtros['accion']);
            }
            if (!empty($filtros['severidad'])) {
                $query->where('severidad', $filtros['severidad']);
            }
            if (!empty($filtros['usuario_id'])) {
                $query->where('usuario_id', (int) $filtros['usuario_id']);
            }
            if (!empty($filtros['usuario_email'])) {
                $query->where('usuario_email', 'like', '%' . $filtros['usuario_email'] . '%');
            }
            if (!empty($filtros['ip'])) {
                $query->where('ip', $filtros['ip']);
            }
            if (!empty($filtros['desde'])) {
                $query->where('created_at', '>=', new \DateTime($filtros['desde']));
            }
            if (!empty($filtros['hasta'])) {
                $query->where('created_at', '<=', new \DateTime($filtros['hasta'] . ' 23:59:59'));
            }

            // Búsqueda libre en mensaje, accion, módulo y url.
            if (!empty($filtros['q'])) {
                $termino = $filtros['q'];
                $query->where(function ($q) use ($termino) {
                    $q->where('mensaje', 'like', "%{$termino}%")
                      ->orWhere('accion', 'like', "%{$termino}%")
                      ->orWhere('modulo', 'like', "%{$termino}%")
                      ->orWhere('http.url', 'like', "%{$termino}%");
                });
            }

            $paginador = $query->orderByDesc('created_at')->paginate($perPage);

            return ApiResponse::success(
                data: [
                    'filtros'    => $filtros,
                    'paginacion' => [
                        'total'        => $paginador->total(),
                        'per_page'     => $paginador->perPage(),
                        'current_page' => $paginador->currentPage(),
                        'last_page'    => $paginador->lastPage(),
                    ],
                    'items'      => AuditLogResource::collection($paginador->items()),
                ],
                message: 'Bitácora consultada correctamente.',
            );
        } catch (\Throwable $th) {
            return ApiResponse::error(
                message: 'Error al consultar la bitácora: ' . $th->getMessage(),
                status: 500,
            );
        }
    }

    /**
     * Detalle de un evento de auditoría.
     */
    public function show(string $id): JsonResponse
    {
        $log = AuditLog::find($id);

        if (! $log) {
            return ApiResponse::error(message: 'Registro de auditoría no encontrado.', status: 404);
        }

        return ApiResponse::success(
            data: new AuditLogResource($log),
            message: 'Registro de auditoría obtenido correctamente.',
        );
    }

    /**
     * Resumen estadístico de la bitácora (últimos 30 días por defecto).
     * Útil para dashboards.
     *
     * Filtros opcionales: ?desde=&hasta=
     */
    public function resumen(): JsonResponse
    {
        try {
            $desde = request('desde')
                ? new \DateTime(request('desde'))
                : (new \DateTime())->modify('-30 days');
            $hasta = request('hasta')
                ? new \DateTime(request('hasta') . ' 23:59:59')
                : new \DateTime();

            $base = AuditLog::query()
                ->where('created_at', '>=', $desde)
                ->where('created_at', '<=', $hasta);

            $registros = (clone $base)->get();

            $totalEventos      = $registros->count();
            $porSeveridad      = $registros->groupBy('severidad')->map->count();
            $porModulo         = $registros->groupBy('modulo')->map->count();
            $porAccion         = $registros->groupBy('accion')->map->count()->sortDesc()->take(10);
            $porDia            = $registros->groupBy(fn ($r) => $r->created_at?->format('Y-m-d'))->map->count();
            $usuariosUnicos    = $registros->pluck('usuario_id')->filter()->unique()->count();
            $loginsExitosos    = $registros->where('accion', 'auth.login.success')->count();
            $loginsFallidos    = $registros->where('accion', 'auth.login.failed')->count();

            return ApiResponse::success(
                data: [
                    'periodo' => [
                        'desde' => $desde->format(DATE_ATOM),
                        'hasta' => $hasta->format(DATE_ATOM),
                    ],
                    'totales' => [
                        'eventos'         => $totalEventos,
                        'usuarios_unicos' => $usuariosUnicos,
                        'logins_exitosos' => $loginsExitosos,
                        'logins_fallidos' => $loginsFallidos,
                    ],
                    'por_severidad' => $porSeveridad,
                    'por_modulo'    => $porModulo,
                    'top_acciones'  => $porAccion,
                    'por_dia'       => $porDia,
                ],
                message: 'Resumen de auditoría generado correctamente.',
            );
        } catch (\Throwable $th) {
            return ApiResponse::error(
                message: 'Error al generar el resumen: ' . $th->getMessage(),
                status: 500,
            );
        }
    }

    /**
     * Búsqueda en la colección de snapshots de transacciones.
     */
    public function snapshots(BuscarSnapshotsRequest $request): JsonResponse
    {
        try {
            $filtros = $request->validated();
            $perPage = (int) ($filtros['per_page'] ?? 25);

            $query = TransaccionSnapshot::query();

            if (!empty($filtros['transaccion_id'])) {
                $query->where('transaccion_id_sql', (int) $filtros['transaccion_id']);
            }
            if (!empty($filtros['cliente_id'])) {
                $cid = (int) $filtros['cliente_id'];
                $query->where(function ($q) use ($cid) {
                    $q->where('cuenta_origen.cliente.id', $cid)
                      ->orWhere('cuenta_destino.cliente.id', $cid);
                });
            }
            if (!empty($filtros['cuenta_id'])) {
                $cid = (int) $filtros['cuenta_id'];
                $query->where(function ($q) use ($cid) {
                    $q->where('cuenta_origen.id', $cid)
                      ->orWhere('cuenta_destino.id', $cid);
                });
            }
            if (!empty($filtros['numero_cuenta'])) {
                $num = $filtros['numero_cuenta'];
                $query->where(function ($q) use ($num) {
                    $q->where('cuenta_origen.numero_cuenta', $num)
                      ->orWhere('cuenta_destino.numero_cuenta', $num);
                });
            }
            if (!empty($filtros['tipo'])) {
                $query->where('tipo_transaccion', $filtros['tipo']);
            }
            if (!empty($filtros['estado'])) {
                $query->where('estado', $filtros['estado']);
            }
            if (!empty($filtros['moneda'])) {
                $query->where('moneda', $filtros['moneda']);
            }
            if (isset($filtros['es_externa'])) {
                $query->where('es_externa', (bool) $filtros['es_externa']);
            }
            if (!empty($filtros['banco_externo'])) {
                $query->where('banco_externo', 'like', '%' . $filtros['banco_externo'] . '%');
            }
            if (isset($filtros['monto_min'])) {
                $query->where('monto', '>=', (float) $filtros['monto_min']);
            }
            if (isset($filtros['monto_max'])) {
                $query->where('monto', '<=', (float) $filtros['monto_max']);
            }
            if (!empty($filtros['desde'])) {
                $query->where('created_at', '>=', new \DateTime($filtros['desde']));
            }
            if (!empty($filtros['hasta'])) {
                $query->where('created_at', '<=', new \DateTime($filtros['hasta'] . ' 23:59:59'));
            }

            $paginador = $query->orderByDesc('created_at')->paginate($perPage);

            return ApiResponse::success(
                data: [
                    'filtros'    => $filtros,
                    'paginacion' => [
                        'total'        => $paginador->total(),
                        'per_page'     => $paginador->perPage(),
                        'current_page' => $paginador->currentPage(),
                        'last_page'    => $paginador->lastPage(),
                    ],
                    'items'      => TransaccionSnapshotResource::collection($paginador->items()),
                ],
                message: 'Snapshots de transacciones consultados correctamente.',
            );
        } catch (\Throwable $th) {
            return ApiResponse::error(
                message: 'Error al consultar los snapshots: ' . $th->getMessage(),
                status: 500,
            );
        }
    }

    /**
     * Detalle de un snapshot.
     */
    public function snapshotShow(string $id): JsonResponse
    {
        $snapshot = TransaccionSnapshot::find($id);

        if (! $snapshot) {
            return ApiResponse::error(message: 'Snapshot no encontrado.', status: 404);
        }

        return ApiResponse::success(
            data: new TransaccionSnapshotResource($snapshot),
            message: 'Snapshot obtenido correctamente.',
        );
    }
}
