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

    /**
     * @OA\Get(
     *     path="/cuentas/search/{numero_cuenta}",
     *     summary="Buscar cuenta por número",
     *     description="Retorna los datos de una cuenta dado su número. Requiere token Sanctum y el permiso `cuentas.search` (roles: admin, banco).",
     *     operationId="searchCuenta",
     *     tags={"Cuentas"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="numero_cuenta",
     *         in="path",
     *         required=true,
     *         description="Número de cuenta a buscar.",
     *         @OA\Schema(type="integer", example=100000001)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Cuenta encontrada correctamente.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status",  type="boolean", example=true),
     *             @OA\Property(property="message", type="string",  example="Cuenta encontrada correctamente."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id",               type="integer", example=1),
     *                 @OA\Property(property="id_cliente",       type="integer", example=5),
     *                 @OA\Property(property="numero_cuenta",    type="integer", example=100000001),
     *                 @OA\Property(property="saldo",            type="number",  format="float", example=1500.00),
     *                 @OA\Property(property="saldo_disponible", type="number",  format="float", example=1500.00),
     *                 @OA\Property(property="moneda",           type="string",  example="Q"),
     *                 @OA\Property(property="estado",           type="string",  example="activa"),
     *                 @OA\Property(property="cliente",          type="object")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado.",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Unauthenticated."))
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Sin permiso.",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="User does not have the right permissions."))
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Cuenta no encontrada.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status",  type="boolean", example=false),
     *             @OA\Property(property="message", type="string",  example="Cuenta no encontrada.")
     *         )
     *     )
     * )
     */
    public function searchAccount(int $numero_cuenta): JsonResponse
    {
        try {

            $cuenta = Cuentas::where('numero_cuenta', $numero_cuenta)->where('estado', true)->first();
            
            if (!$cuenta) {
                return ApiResponse::error(message: 'Cuenta no encontrada.', status: 404);
            }

            $cuenta->load('cliente');
            $cuenta->makeHidden(['saldo', 'saldo_disponible']);

            return ApiResponse::success(
                data: CuentaResource::make($cuenta)->hide(['saldo', 'saldo_disponible']),
                message: 'Cuenta encontrada correctamente.'
            );

        } catch (\Throwable $th) {
            return ApiResponse::error(message: 'Error al buscar la cuenta.', status: 500);
        }
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
