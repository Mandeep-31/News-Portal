<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use App\Services\AdminAuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class AdminAuthController extends Controller
{
    public function __construct(private readonly AdminAuthService $authentication) {}

    public function login(AdminLoginRequest $request): JsonResponse
    {
        $token = $this->authentication->issueApiToken(
            $request->validated('email'),
            $request->validated('password'),
        );

        if ($token === null) {
            return ApiResponse::error('Invalid email or password.', 401);
        }

        return ApiResponse::success('Login successful.', [
            'token_type' => 'Bearer',
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $this->authentication->revokeApiToken($token);
        }

        return ApiResponse::success('Logged out.');
    }
}
