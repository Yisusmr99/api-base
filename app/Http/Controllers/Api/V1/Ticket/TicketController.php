<?php

namespace App\Http\Controllers\Api\V1\Ticket;

use App\Enums\EstadoTicket;
use App\Enums\TipoMovimientoTicket;
use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Http\Requests\Api\V1\Ticket\AssignTicketRequest;
use App\Http\Requests\Api\V1\Ticket\ChangeTicketStatusRequest;
use App\Http\Requests\Api\V1\Ticket\CloseTicketRequest;
use App\Http\Requests\Api\V1\Ticket\ReassignTicketRequest;
use App\Http\Requests\Api\V1\Ticket\StoreTicketRequest;
use App\Http\Requests\Api\V1\Ticket\UpdateTicketRequest;
use App\Http\Resources\Api\V1\TicketResource;
use App\Models\AsignacionTicket;
use App\Models\HistorialTicket;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    public function index(): JsonResponse
    {
        $tickets = Ticket::with(['cliente', 'creador'])
            ->withCount(['asignaciones', 'historiales'])
            ->paginate(15);

        return ApiResponse::success(
            data: [
                'tickets' => TicketResource::collection($tickets->items()),
                'meta' => [
                    'current_page' => $tickets->currentPage(),
                    'last_page' => $tickets->lastPage(),
                    'per_page' => $tickets->perPage(),
                    'total' => $tickets->total(),
                ],
            ],
            message: 'Tickets obtenidos correctamente.'
        );
    }

    public function indexAll(): JsonResponse
    {
        $tickets = Ticket::with(['cliente', 'creador'])
            ->withCount(['asignaciones', 'historiales'])
            ->get();

        return ApiResponse::success(
            data: TicketResource::collection($tickets),
            message: 'Tickets obtenidos correctamente.'
        );
    }

    public function show(int $id): JsonResponse
    {
        $ticket = Ticket::with(['cliente', 'creador'])
            ->withCount(['asignaciones', 'historiales'])
            ->findOrFail($id);

        return ApiResponse::success(
            data: new TicketResource($ticket),
            message: 'Ticket obtenido correctamente.'
        );
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        $ticket = DB::transaction(function () use ($request) {
            $validated = $request->validated();
            $userId = (int) $request->user()->id;

            $ticket = Ticket::create(array_merge($validated, [
                'creado_por' => $validated['creado_por'] ?? $userId,
            ]));

            $this->registrarHistorial(
                ticket: $ticket,
                userId: $userId,
                tipoMovimiento: TipoMovimientoTicket::Creacion,
                estadoAnterior: null,
                estadoNuevo: $ticket->id_estado_ticket,
                descripcion: 'Creación del ticket.'
            );

            return $ticket;
        });

        $ticket->load(['cliente', 'creador'])->loadCount(['asignaciones', 'historiales']);

        return ApiResponse::success(
            data: new TicketResource($ticket),
            message: 'Ticket creado correctamente.',
            status: 201
        );
    }

    public function update(UpdateTicketRequest $request, int $id): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);

        DB::transaction(function () use ($request, $ticket): void {
            $estadoAnterior = $ticket->id_estado_ticket;
            $validated = $request->validated();
            $ticket->update($validated);

            if (array_key_exists('id_estado_ticket', $validated) && $ticket->id_estado_ticket !== $estadoAnterior) {
                if ($ticket->id_estado_ticket === EstadoTicket::Cerrado) {
                    AsignacionTicket::query()
                        ->where('id_ticket', $ticket->id_ticket)
                        ->where('estado_asignacion', 'activa')
                        ->update(['estado_asignacion' => 'finalizada']);

                    if ($ticket->fecha_cierre === null) {
                        $ticket->update(['fecha_cierre' => now()]);
                    }
                }

                $this->registrarHistorial(
                    ticket: $ticket,
                    userId: (int) $request->user()->id,
                    tipoMovimiento: TipoMovimientoTicket::CambioEstado,
                    estadoAnterior: $estadoAnterior,
                    estadoNuevo: $ticket->id_estado_ticket,
                    descripcion: 'Cambio de estado desde actualización de ticket.'
                );
            }
        });

        $ticket->load(['cliente', 'creador'])->loadCount(['asignaciones', 'historiales']);

        return ApiResponse::success(
            data: new TicketResource($ticket->fresh()),
            message: 'Ticket actualizado correctamente.'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);
        $ticket->delete();

        return ApiResponse::success(message: 'Ticket eliminado correctamente.');
    }

    public function assign(AssignTicketRequest $request, int $id): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);
        $usuarioAsignaId = (int) $request->user()->id;
        $validated = $request->validated();

        $asignacionActiva = AsignacionTicket::query()
            ->where('id_ticket', $ticket->id_ticket)
            ->where('estado_asignacion', 'activa')
            ->first();

        if ($asignacionActiva !== null) {
            return ApiResponse::error(
                message: 'El ticket ya tiene una asignación activa. Use el endpoint de reasignación.',
                status: 422
            );
        }

        DB::transaction(function () use ($ticket, $usuarioAsignaId, $validated): void {
            AsignacionTicket::create([
                'id_ticket' => $ticket->id_ticket,
                'id_usuario_asignado' => $validated['id_usuario_asignado'],
                'id_usuario_asigna' => $usuarioAsignaId,
                'motivo_asignacion' => $validated['motivo_asignacion'] ?? null,
                'estado_asignacion' => 'activa',
            ]);

            $estadoAnterior = $ticket->id_estado_ticket;
            $ticket->update(['id_estado_ticket' => EstadoTicket::Asignado->value]);

            $this->registrarHistorial(
                ticket: $ticket,
                userId: $usuarioAsignaId,
                tipoMovimiento: TipoMovimientoTicket::Reasignacion,
                estadoAnterior: $estadoAnterior,
                estadoNuevo: $ticket->id_estado_ticket,
                descripcion: $validated['motivo_asignacion'] ?? 'Asignación inicial del ticket.'
            );
        });

        $ticket->load(['cliente', 'creador'])->loadCount(['asignaciones', 'historiales']);

        return ApiResponse::success(
            data: new TicketResource($ticket->fresh()),
            message: 'Ticket asignado correctamente.'
        );
    }

    public function reassign(ReassignTicketRequest $request, int $id): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);
        $validated = $request->validated();
        $usuarioAsignaId = (int) $request->user()->id;

        $asignacionActiva = AsignacionTicket::query()
            ->where('id_ticket', $ticket->id_ticket)
            ->where('estado_asignacion', 'activa')
            ->first();

        if ($asignacionActiva === null) {
            return ApiResponse::error(
                message: 'No existe una asignación activa para reasignar.',
                status: 422
            );
        }

        DB::transaction(function () use ($ticket, $validated, $usuarioAsignaId, $asignacionActiva): void {
            $asignacionActiva->update(['estado_asignacion' => 'reasignada']);

            AsignacionTicket::create([
                'id_ticket' => $ticket->id_ticket,
                'id_usuario_asignado' => $validated['id_usuario_asignado'],
                'id_usuario_asigna' => $usuarioAsignaId,
                'motivo_asignacion' => $validated['motivo_asignacion'],
                'estado_asignacion' => 'activa',
            ]);

            $estadoAnterior = $ticket->id_estado_ticket;
            $ticket->update(['id_estado_ticket' => EstadoTicket::Asignado->value]);

            $this->registrarHistorial(
                ticket: $ticket,
                userId: $usuarioAsignaId,
                tipoMovimiento: TipoMovimientoTicket::Reasignacion,
                estadoAnterior: $estadoAnterior,
                estadoNuevo: $ticket->id_estado_ticket,
                descripcion: $validated['motivo_asignacion']
            );
        });

        $ticket->load(['cliente', 'creador'])->loadCount(['asignaciones', 'historiales']);

        return ApiResponse::success(
            data: new TicketResource($ticket->fresh()),
            message: 'Ticket reasignado correctamente.'
        );
    }

    public function changeStatus(ChangeTicketStatusRequest $request, int $id): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);
        $nuevoEstado = EstadoTicket::from($request->validated('id_estado_ticket'));
        $estadoAnterior = $ticket->id_estado_ticket;

        if ($estadoAnterior === $nuevoEstado) {
            return ApiResponse::error(
                message: 'El ticket ya se encuentra en el estado indicado.',
                status: 422
            );
        }

        DB::transaction(function () use ($ticket, $request, $nuevoEstado, $estadoAnterior): void {
            $ticket->update([
                'id_estado_ticket' => $nuevoEstado->value,
                'fecha_cierre' => $nuevoEstado === EstadoTicket::Cerrado ? now() : null,
            ]);

            if ($nuevoEstado === EstadoTicket::Cerrado) {
                AsignacionTicket::query()
                    ->where('id_ticket', $ticket->id_ticket)
                    ->where('estado_asignacion', 'activa')
                    ->update(['estado_asignacion' => 'finalizada']);
            }

            $this->registrarHistorial(
                ticket: $ticket,
                userId: (int) $request->user()->id,
                tipoMovimiento: TipoMovimientoTicket::CambioEstado,
                estadoAnterior: $estadoAnterior,
                estadoNuevo: $ticket->id_estado_ticket,
                descripcion: $request->validated('descripcion')
            );
        });

        $ticket->load(['cliente', 'creador'])->loadCount(['asignaciones', 'historiales']);

        return ApiResponse::success(
            data: new TicketResource($ticket->fresh()),
            message: 'Estado del ticket actualizado correctamente.'
        );
    }

    public function close(CloseTicketRequest $request, int $id): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);
        $estadoAnterior = $ticket->id_estado_ticket;

        if ($estadoAnterior === EstadoTicket::Cerrado) {
            return ApiResponse::error(
                message: 'El ticket ya está cerrado.',
                status: 422
            );
        }

        DB::transaction(function () use ($ticket, $request, $estadoAnterior): void {
            $ticket->update([
                'id_estado_ticket' => EstadoTicket::Cerrado->value,
                'fecha_cierre' => now(),
            ]);

            AsignacionTicket::query()
                ->where('id_ticket', $ticket->id_ticket)
                ->where('estado_asignacion', 'activa')
                ->update(['estado_asignacion' => 'finalizada']);

            $this->registrarHistorial(
                ticket: $ticket,
                userId: (int) $request->user()->id,
                tipoMovimiento: TipoMovimientoTicket::Cierre,
                estadoAnterior: $estadoAnterior,
                estadoNuevo: $ticket->id_estado_ticket,
                descripcion: $request->validated('descripcion') ?? 'Cierre del ticket.'
            );
        });

        $ticket->load(['cliente', 'creador'])->loadCount(['asignaciones', 'historiales']);

        return ApiResponse::success(
            data: new TicketResource($ticket->fresh()),
            message: 'Ticket cerrado correctamente.'
        );
    }

    private function registrarHistorial(
        Ticket $ticket,
        int $userId,
        TipoMovimientoTicket $tipoMovimiento,
        ?EstadoTicket $estadoAnterior,
        ?EstadoTicket $estadoNuevo,
        ?string $descripcion
    ): void {
        HistorialTicket::create([
            'id_ticket' => $ticket->id_ticket,
            'id_usuario' => $userId,
            'tipo_movimiento' => $tipoMovimiento->value,
            'descripcion' => $descripcion,
            'estado_anterior' => $estadoAnterior?->value,
            'estado_nuevo' => $estadoNuevo?->value,
        ]);
    }
}
