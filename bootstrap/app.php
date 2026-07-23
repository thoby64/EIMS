<?php

use App\Http\Middleware\AuditHttpRequest;
use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\TrustProxies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/health',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust proxy headers from Render reverse proxy
        $middleware->web(prepend: [TrustProxies::class], append: [AuditHttpRequest::class]);
        $middleware->alias([
            'active' => EnsureActiveUser::class,
            'permission' => EnsurePermission::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
