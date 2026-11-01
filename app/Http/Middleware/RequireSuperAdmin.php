<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class RequireSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if (
            ! $token instanceof PersonalAccessToken
            || ! $token->can('super-admin')
        ) {
            return ApiResponse::error('Super administrator token required.', 403);
        }

        if (! $user || ! $user->isSuperAdmin()) {
            return ApiResponse::error('Super administrator access required.', 403);
        }

        return $next($request);
    }
}