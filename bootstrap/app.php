<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Excluir rutas públicas de verificación CSRF
        $middleware->validateCsrfTokens(except: [
            'consultar/*',
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\EnsureWorkspaceSelected::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $expiredSessionRedirect = function (Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'La sesion expiro. Selecciona nuevamente el entorno o inicia sesion.',
                ], 419);
            }

            if (auth()->check()) {
                return redirect()
                    ->route('workspaces.select')
                    ->with('warning', 'La sesion expiro. Selecciona nuevamente el entorno.');
            }

            return redirect()
                ->route('login')
                ->with('warning', 'La sesion expiro. Inicia sesion nuevamente.');
        };

        $exceptions->render(function (TokenMismatchException $exception, Request $request) use ($expiredSessionRedirect) {
            return $expiredSessionRedirect($request);
        });

        $exceptions->render(function (HttpException $exception, Request $request) use ($expiredSessionRedirect) {
            if ($exception->getStatusCode() !== 419) {
                return null;
            }

            return $expiredSessionRedirect($request);
        });
    })->create();
