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
    private const REFRESH_COOKIE = 'admin_refresh_token';

    private function refreshCookie(string $token)
{
    return cookie(
        self::REFRESH_COOKIE,
        $token,
        7 * 24 * 60,
        '/api/admin/refresh',
        null,
        (bool) config('session.refresh_cookie_secure', false),
        true,
        false,
        config('session.refresh_cookie_same_site', 'lax')
    );
}

    private function clearRefreshCookie()
    {
        return cookie(
            self::REFRESH_COOKIE,
            '',
            -1,
            '/api/admin/refresh',
            null,
            app()->environment('production'),
            true,
            false,
            'lax'
        );
    }

    public function login(AdminLoginRequest $request): JsonResponse
    {
        $result = $this->authentication->issueApiToken(
            $request->validated('email'),
            $request->validated('password'),
        );

        if ($result === null) {
            return ApiResponse::error(
                'Invalid email or password.',
                401
            );
        }

        $admin = $result['user'];

        return ApiResponse::success('Login successful.', [
            'user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => $admin->role,
            ],
            'token_type' => 'Bearer',
            'token' => $result['token'],
            'expires_in' => $result['expires_in'],
        ])->withCookie(
            $this->refreshCookie($result['refresh_token'])
        );
    }

    public function refresh(Request $request): JsonResponse
    {
        $refreshToken = $request->cookie(self::REFRESH_COOKIE);

        if (! $refreshToken) {
            return ApiResponse::error(
                'Refresh token is missing. Please log in again.',
                401
            )->withCookie($this->clearRefreshCookie());
        }

        $result = $this->authentication->refreshApiToken(
            $refreshToken
        );

        if ($result === null) {
            return ApiResponse::error(
                'Refresh token is invalid, expired, or revoked. Please log in again.',
                401
            )->withCookie($this->clearRefreshCookie());
        }

        $admin = $result['user'];

        return ApiResponse::success('Token refreshed.', [
            'user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => $admin->role,
            ],
            'token_type' => 'Bearer',
            'token' => $result['token'],
            'expires_in' => $result['expires_in'],
        ])->withCookie(
            $this->refreshCookie($result['refresh_token'])
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $this->authentication->revokeApiToken($token);
        }

        return ApiResponse::success('Logged out.')
            ->withCookie($this->clearRefreshCookie());
    }

    public function __construct(
        private readonly AdminAuthService $authentication
    ) {}
}
