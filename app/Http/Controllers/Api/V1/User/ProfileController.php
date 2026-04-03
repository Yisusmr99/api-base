<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success(
            data: new UserResource($request->user()),
            message: 'Perfil obtenido correctamente.'
        );
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'   => ['sometimes', 'string', 'max:255'],
            'email'  => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'estado' => ['sometimes', 'boolean'],
        ]);

        $user->update($validated);

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

    public function store(Request $request): JsonResponse
    {
        // dd('el store');
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'role'     => ['required', 'string', 'exists:roles,name'],
            'estado'   => ['sometimes', 'boolean'],
        ]);

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

    public function updateById(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'     => ['sometimes', 'string', 'max:255'],
            'email'    => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['sometimes', 'string', 'min:8'],
            'role'     => ['sometimes', 'string', 'exists:roles,name'],
            'estado'   => ['sometimes', 'boolean'],
        ]);

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
