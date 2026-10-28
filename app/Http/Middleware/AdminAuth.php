<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use App\Support\ApiResponse;
use Symfony\Component\HttpFoundation\Response;
class AdminAuth
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        if (! session('is_admin')) {
            if ($request->expectsJson()) {
                return ApiResponse::error('Unauthenticated.', 401);
            }

            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
