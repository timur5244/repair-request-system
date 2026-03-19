<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'dispatcher' => \App\Http\Middleware\DispatcherOnly::class,
            'master' => \App\Http\Middleware\MasterOnly::class,
        ]);

        // Для удобства ручного race-теста через curl (локально): POST эндпоинты мастера без CSRF.
        $middleware->validateCsrfTokens(except: [
            'master/requests/*/take',
            'master/requests/*/finish',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
