<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Http\Requests\Api\V1\User\StoreUserRequest;
use App\Http\Requests\Api\V1\User\UpdateProfileRequest;
use App\Http\Requests\Api\V1\User\UpdateUserRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function show(UpdateProfileRequest $request): JsonResponse
    {
        return ApiResponse::success(
            data: new UserResource($request->user()),
            message: 'Perfil obtenido correctamente.'
        );
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->update($request->validated());

        return ApiResponse::success(
            data: new UserResource($user->fresh()),
            message: 'Perfil actualizado correctamente.'
        );
    }

    public function index(): JsonResponse
    {
        $users = User::with('roles')->paginate(15);

        return ApiResponse::success(
            data: [
                'users' => UserResource::collection($users->items()),
                'meta'  => [
                    'current_page' => $users->currentPage(),
                    'last_page'    => $users->lastPage(),
                    'per_page'     => $users->perPage(),
                    'total'        => $users->total(),
                ],
            ],
            message: 'Usuarios obtenidos correctamente.'
        );
    }

    public function showById(int $id): JsonResponse
    {
        $user = User::with('roles')->findOrFail($id);

        return ApiResponse::success(
            data: new UserResource($user),
            message: 'Usuario obtenido correctamente.'
        );
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => $validated['password'],
            'estado'   => $validated['estado'] ?? true,
        ]);

        $user->assignRole($validated['role']);

        return ApiResponse::success(
            data: new UserResource($user->load('roles')),
            message: 'Usuario creado correctamente.',
            status: 201
        );
    }

    public function updateById(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validated();

        $user->update(collect($validated)->except('role')->toArray());

        if (isset($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }

        return ApiResponse::success(
            data: new UserResource($user->fresh()->load('roles')),
            message: 'Usuario actualizado correctamente.'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->delete();

        return ApiResponse::success(message: 'Usuario eliminado correctamente.');
    }
}
