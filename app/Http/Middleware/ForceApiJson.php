<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceApiJson
{
    public function handle(Request $request, Closure $next): Response
    {
        // Ensure validation, authentication, and framework errors use JSON
        // even when Postman sends Accept: */* or omits the Accept header.
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
