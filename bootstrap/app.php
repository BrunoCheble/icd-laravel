<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Production: Laravel's caches rebuilt after each deploy (the deploy only pulls the code).
        $middleware->append(\App\Http\Middleware\RefreshDeployCaches::class);
        // Only the system and the church's site may show its pages inside theirs.
        $middleware->append(\App\Http\Middleware\LimitFraming::class);
        // Out of the search engines (the church is found by its site).
        $middleware->append(\App\Http\Middleware\PreventIndexing::class);
        // Production: the app's notifications, once an hour, after a request (see SendPushDigestService).
        $middleware->append(\App\Http\Middleware\RunPushDigest::class);
        // The app's lock for passkey users (fingerprint / face to come back after a while away).
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureSessionUnlocked::class);
        // A temporary password is changed first; then each user reaches only what they may (see Permissions).
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsurePasswordChanged::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsurePermission::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
