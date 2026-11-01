<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class RequireAdminToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if (! $token instanceof PersonalAccessToken || ! $token->can('admin')) {
            return ApiResponse::error('Administrator token required.', 403);
        }

        if (! $user || ! $user->isAdmin() || $user->trashed()) {
            return ApiResponse::error('Administrator access required.', 403);
        }

        return $next($request);
    }
}
