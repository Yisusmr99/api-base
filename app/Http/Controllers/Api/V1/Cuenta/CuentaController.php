<?php

namespace App\Http\Controllers\Api\V1\Cuenta;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Http\Helpers\DateHelper;
use App\Http\Requests\Api\V1\Cuenta\StoreCuentaRequest;
use App\Http\Requests\Api\V1\Cuenta\UpdateCuentaRequest;
use App\Http\Resources\Api\V1\CuentaResource;
use App\Models\Cuentas;
use Illuminate\Http\JsonResponse;

class CuentaController extends Controller
{
    public function index(): JsonResponse
    {
        $cuentas = Cuentas::with('cliente')->paginate(15);

        return ApiResponse::success(
            data: [
                'cuentas' => CuentaResource::collection($cuentas->items()),
                'meta' => [
                    'current_page' => $cuentas->currentPage(),
                    'last_page' => $cuentas->lastPage(),
                    'per_page' => $cuentas->perPage(),
                    'total' => $cuentas->total(),
                ],
            ],
            message: 'Cuentas obtenidas correctamente.'
        );
    }

    public function indexAll(): JsonResponse
    {
        $cuentas = Cuentas::with('cliente')->get();

        return ApiResponse::success(
            data: CuentaResource::collection($cuentas),
            message: 'Cuentas obtenidas correctamente.'
        );
    }

    public function show(int $id): JsonResponse
    {
        $cuenta = Cuentas::with('cliente')->findOrFail($id);

        return ApiResponse::success(
            data: new CuentaResource($cuenta),
            message: 'Cuenta obtenida correctamente.'
        );
    }

    public function store(StoreCuentaRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $cuenta = Cuentas::create(array_merge($validated, [
            'saldo' => $validated['saldo_disponible'],
            'fecha_apertura' => DateHelper::now(),
            'estado' => true,
        ]));

        $cuenta->load('cliente');

        return ApiResponse::success(
            data: new CuentaResource($cuenta),
            message: 'Cuenta creada correctamente.',
            status: 201
        );
    }

    public function update(UpdateCuentaRequest $request, int $id): JsonResponse
    {
        $cuenta = Cuentas::findOrFail($id);

        $cuenta->update($request->validated());
        $cuenta->load('cliente');

        return ApiResponse::success(
            data: new CuentaResource($cuenta->fresh()),
            message: 'Cuenta actualizada correctamente.'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $cuenta = Cuentas::findOrFail($id);

        $cuenta->update([
            'estado' => false,
            'fecha_cierre' => DateHelper::now(),
        ]);

        $cuenta->load('cliente');

        return ApiResponse::success(
            data: new CuentaResource($cuenta),
            message: 'Cuenta cerrada correctamente.'
        );
    }
}
