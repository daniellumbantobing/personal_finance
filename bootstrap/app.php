<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust all proxies for Vercel serverless reverse proxy
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

// Support dynamic serverless storage paths (e.g. /tmp/storage on Vercel)
if ($storagePath = env('APP_STORAGE', env('LARAVEL_STORAGE_PATH'))) {
    $app->useStoragePath($storagePath);
}

// Force array cache store to prevent PostgreSQL PgBouncer transaction aborts on serverless
$app->booting(function () {
    config([
        'cache.default' => 'array',
        'cache.limiter' => 'array',
        'cache.stores.database.driver' => 'array',
    ]);
});

return $app;
