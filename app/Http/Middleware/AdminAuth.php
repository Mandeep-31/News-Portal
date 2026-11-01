<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        if (! session('is_admin')) {
            if ($request->expectsJson() || str_contains((string) $request->userAgent(), 'PostmanRuntime')) {
                return ApiResponse::error('Unauthenticated.', 401);
            }

            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
