<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Helpers\ApiResponse;

class RefreshTokenController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->currentAccessToken()->delete();

        $token = $user->createToken(
            name: 'auth_token',
            expiresAt: now()->addMinutes(config('sanctum.expiration'))
        );

        return ApiResponse::success(
            data: [
                'user'    => new UserResource($user),
                'token'   => $token->plainTextToken,
            ],
            message: 'Token renovado exitosamente.'
        );
    }
}
