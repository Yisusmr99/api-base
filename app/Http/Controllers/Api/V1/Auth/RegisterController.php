<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use App\Http\Helpers\ApiResponse;

class RegisterController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);
        // TODO: Ver la forma de como asignar el rol

        $token = $user->createToken(
            name: 'auth_token',
            expiresAt: now()->addMinutes(config('sanctum.expiration', 300))
        );

        return ApiResponse::success(
            data: [
                'user' => new UserResource($user),
                'token' => $token,
            ],
            message: 'Usuario registrado exitosamente.',
            status: 201
        );
    }


}
