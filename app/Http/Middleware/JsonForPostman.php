<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JsonForPostman
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            str_contains((string) $request->userAgent(), 'PostmanRuntime')
            && ! filter_var($request->header('X-Use-Web-Session'), FILTER_VALIDATE_BOOLEAN)
        ) {
            $request->headers->set('Accept', 'application/json');
        }

        return $next($request);
    }
}
