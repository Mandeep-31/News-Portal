<?php

use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\ForceApiJson;
use App\Http\Middleware\JsonForPostman;
use App\Http\Middleware\RequireAdminToken;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.auth' => AdminAuth::class,
            'admin.token' => RequireAdminToken::class,
        ]);
        $middleware->appendToGroup('api', [
            ForceApiJson::class,
        ]);
        $middleware->append(JsonForPostman::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $expectsJson = fn (Request $request): bool => $request->is('api/*')
                || $request->expectsJson()
                || str_contains((string) $request->userAgent(), 'PostmanRuntime');

        $exceptions->shouldRenderJsonWhen($expectsJson);

        $exceptions->render(function (Throwable $exception, Request $request) use ($expectsJson) {
            if (! $expectsJson($request)) {
                return null;
            }

            if ($exception instanceof ValidationException) {
                $errors = $exception->errors();
                $message = $errors['category'][0] ?? 'The given data was invalid.';

                return ApiResponse::error($message, 422, $errors);
            }

            if ($exception instanceof AuthenticationException) {
                return ApiResponse::error('Unauthenticated.', 401);
            }

            if ($exception instanceof AuthorizationException) {
                return ApiResponse::error('Forbidden.', 403);
            }

            if ($exception instanceof TokenMismatchException) {
                return ApiResponse::error('CSRF token mismatch.', 419);
            }

            if ($exception instanceof ModelNotFoundException) {
                $message = $request->is('api/news/*') ? 'News not found.' : 'Resource not found.';

                return ApiResponse::error($message, 404);
            }

            if ($exception instanceof HttpResponseException) {
                $status = $exception->getResponse()->getStatusCode();

                return ApiResponse::error($status === 419 ? 'CSRF token mismatch.' : 'Request failed.', $status);
            }

            if ($exception instanceof HttpExceptionInterface) {
                $status = $exception->getStatusCode();
                $message = match ($status) {
                    401 => 'Unauthenticated.',
                    403 => 'Forbidden.',
                    404 => $request->route('slug') ? 'News not found.' : 'Endpoint not found.',
                    405 => 'Method not allowed.',
                    419 => 'CSRF token mismatch.',
                    429 => 'Too many requests.',
                    default => $status >= 500 ? 'Server error.' : 'Request failed.',
                };

                return ApiResponse::error($message, $status);
            }

            return ApiResponse::error('Server error.', 500);
        });
    })->create();
