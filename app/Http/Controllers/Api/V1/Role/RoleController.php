<?php

namespace App\Http\Controllers\Api\V1\Role;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Http\Resources\Api\V1\RoleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Role;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')->paginate(15);

        return ApiResponse::success(
            data: [
                'roles' => RoleResource::collection($roles->items()),
                'meta'  => [
                    'current_page' => $roles->currentPage(),
                    'last_page'    => $roles->lastPage(),
                    'per_page'     => $roles->perPage(),
                    'total'        => $roles->total(),
                ],
            ],
            message: 'Roles obtenidos correctamente.'
        );
    }

    public function indexAll(): JsonResponse
    {
        $roles = Role::with('permissions')->get();

        return ApiResponse::success(
            data: RoleResource::collection($roles),
            message: 'Roles obtenidos correctamente.'
        );
    }

    public function show(int $id): JsonResponse
    {
        $role = Role::with('permissions')->findOrFail($id);

        return ApiResponse::success(
            data: new RoleResource($role),
            message: 'Rol obtenido correctamente.'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
        ]);

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'sanctum']);

        return ApiResponse::success(
            data: new RoleResource($role->load('permissions')),
            message: 'Rol creado correctamente.',
            status: 201
        );
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,' . $role->id],
        ]);

        $role->update(['name' => $validated['name']]);

        return ApiResponse::success(
            data: new RoleResource($role->fresh()->load('permissions')),
            message: 'Rol actualizado correctamente.'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        if ($role->users()->count() > 0) {
            return ApiResponse::error(
                message: 'No se puede eliminar el rol porque tiene usuarios asignados.',
                status: 409
            );
        }

        $role->delete();

        return ApiResponse::success(message: 'Rol eliminado correctamente.');
    }
}
