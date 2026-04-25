<?php

namespace App\Http\Controllers\Api\V1\Cliente;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Http\Requests\Api\V1\Cliente\StoreClienteRequest;
use App\Http\Requests\Api\V1\Cliente\UpdateClienteRequest;
use App\Http\Resources\Api\V1\ClienteResource;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;

class ClienteController extends Controller
{
    public function index(): JsonResponse
    {
        $clientes = Cliente::with('cuentas')->paginate(15);

        return ApiResponse::success(
            data: [
                'clientes' => ClienteResource::collection($clientes->items()),
                'meta'     => [
                    'current_page' => $clientes->currentPage(),
                    'last_page'    => $clientes->lastPage(),
                    'per_page'     => $clientes->perPage(),
                    'total'        => $clientes->total(),
                ],
            ],
            message: 'Clientes obtenidos correctamente.'
        );
    }

    public function indexAll(): JsonResponse
    {
        $clientes = Cliente::all();

        return ApiResponse::success(
            data: ClienteResource::collection($clientes),
            message: 'Clientes obtenidos correctamente.'
        );
    }

    public function show(int $id): JsonResponse
    {
        $cliente = Cliente::with('cuentas')->findOrFail($id);

        return ApiResponse::success(
            data: new ClienteResource($cliente),
            message: 'Cliente obtenido correctamente.'
        );
    }

    public function store(StoreClienteRequest $request): JsonResponse
    {
        $cliente = Cliente::create($request->validated());

        return ApiResponse::success(
            data: new ClienteResource($cliente),
            message: 'Cliente creado correctamente.',
            status: 201
        );
    }

    public function update(UpdateClienteRequest $request, int $id): JsonResponse
    {
        $cliente = Cliente::findOrFail($id);

        $cliente->update($request->validated());

        return ApiResponse::success(
            data: new ClienteResource($cliente->fresh()),
            message: 'Cliente actualizado correctamente.'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->delete();

        return ApiResponse::success(message: 'Cliente eliminado correctamente.');
    }
}
