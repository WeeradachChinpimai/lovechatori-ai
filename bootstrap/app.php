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
        // Honor X-Forwarded-Proto/Host from a TLS-terminating proxy (ngrok,
        // load balancer) so https pages don't emit http (mixed-content) assets.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'access.guard' => \App\Http\Middleware\SimpleAccessGuard::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
