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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\AutoDatabaseBackup::class,
        ]);

        $middleware->alias([
            'ip.track' => \App\Http\Middleware\TrackIpAddress::class,
            'ip.enforce' => \App\Http\Middleware\EnforceIpRules::class,
            'stream.track' => \App\Http\Middleware\TrackStreamAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
