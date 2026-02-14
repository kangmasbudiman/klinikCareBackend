<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . "/../routes/web.php",
        api: __DIR__ . "/../routes/api.php",
        commands: __DIR__ . "/../routes/console.php",
        health: "/up",
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // No stateful middleware for pure token-based API
        // Remove EnsureFrontendRequestsAreStateful to avoid CSRF requirement
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Return JSON for API requests
        $exceptions->shouldRenderJsonWhen(function (
            Request $request,
            Throwable $e,
        ) {
            return $request->is("api/*") || $request->expectsJson();
        });
    })
    ->create();
