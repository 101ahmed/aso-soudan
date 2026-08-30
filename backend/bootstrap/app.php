<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
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
        // Render / reverse proxies (HTTPS termination)
        $middleware->trustProxies(at: '*');
        $middleware->statefulApi();
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Sanctum SPA : session cookie HttpOnly + CSRF (pas de token dans localStorage)
        $middleware->alias([
            'permission' => \App\Http\Middleware\EnsureUserHasPermission::class,
            'department' => \App\Http\Middleware\EnsureDepartmentAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->guest('/login');
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! app()->isProduction()) {
                return null;
            }
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }
            if ($e instanceof ValidationException || $e instanceof AuthenticationException) {
                return null;
            }
            if ($e instanceof HttpExceptionInterface) {
                return null;
            }

            report($e);

            return response()->json([
                'message' => __('auth.generic_error'),
            ], 500);
        });
    })->create();
