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
            'name'  => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
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

    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->delete();

        return ApiResponse::success(message: 'Usuario eliminado correctamente.');
    }
}
