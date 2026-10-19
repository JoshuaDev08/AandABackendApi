<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Services\ApiResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sanctum SPA cookie middleware will be enabled
        // when we implement first-party React authentication.
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->shouldRenderJsonWhen(
            fn(Request $request, Throwable $e) =>
            $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->render(function (
            ValidationException $e,
            Request $request
        ) {
            if (!$request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                'Validation failed.',
                422,
                $e->errors()
            );
        });

        $exceptions->render(function (
            AuthenticationException $e,
            Request $request
        ) {
            if (!$request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                'Unauthenticated.',
                401
            );
        });

        $exceptions->render(function (
            ModelNotFoundException $e,
            Request $request
        ) {
            if (!$request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                'Resource not found.',
                404
            );
        });

        $exceptions->render(function (
            NotFoundHttpException $e,
            Request $request
        ) {
            if (!$request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                'Endpoint or resource not found.',
                404
            );
        });

        $exceptions->render(function (
            HttpExceptionInterface $e,
            Request $request
        ) {
            if (!$request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                $e->getStatusCode() === 403
                    ? 'Forbidden.'
                    : ($e->getStatusCode() >= 500
                        ? 'Server error.'
                        : 'Request failed.'),
                $e->getStatusCode()
            );
        });

        $exceptions->render(function (
            Throwable $e,
            Request $request
        ) {
            if (!$request->is('api/*')) {
                return null;
            }

            $status = $e instanceof HttpExceptionInterface
                ? $e->getStatusCode()
                : 500;

            // Never expose internal exception messages
            // in production responses.
            return ApiResponse::error(
                'An unexpected server error occurred.',
                $status
            );
        });
    })
    ->create();
