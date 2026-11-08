<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateFrontendOrigin
{
   public function handle(Request $request, Closure $next): Response
{
    $origin = $request->header('Origin');

    $trustedOrigins = array_filter(
        explode(',', config('app.frontend_urls'))
    );

    if (! $origin || ! in_array($origin, $trustedOrigins, true)) {
        return response()->json([
            'success' => false,
            'message' => 'Untrusted request origin.',
            'data' => null,
            'errors' => null,
        ], 403);
    }

    return $next($request);
}
}