<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/web.php',
            __DIR__.'/../routes/frontsite.php',
            __DIR__.'/../routes/admin.php',
            __DIR__.'/../routes/admin_api.php',
            __DIR__.'/../routes/admin_seo.php',
            __DIR__.'/../routes/ai.php',
            __DIR__.'/../routes/api_v1/seo.php',
        ],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'frontsite.cache' => \App\Http\Middleware\CacheFrontsiteResponse::class,
            'noindex.headers' => \App\Http\Middleware\AddNoIndexHeaders::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
