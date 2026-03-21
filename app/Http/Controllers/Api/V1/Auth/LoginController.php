<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Http\Helpers\ApiResponse;

class LoginController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            return ApiResponse::error(
                message: 'Credenciales incorrectas.',
                status: 401
            );
        }

        /** @var User $user */
        $user = Auth::user();

        $user->tokens()->where('name', 'auth_token')->delete();

        $token = $user->createToken(
            name: 'auth_token',
            expiresAt: now()->addMinutes(config('sanctum.expiration'))
        );

        return ApiResponse::success(
            data: [
                'user'    => new UserResource($user),
                'token'   => $token->plainTextToken,
            ],
            message: 'Login exitoso.'
        );
    }
}
