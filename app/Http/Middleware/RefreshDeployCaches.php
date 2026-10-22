<?php

namespace App\Http\Middleware;

use App\Services\RefreshDeployCachesService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * In production, after a deploy (or a change to .env), the first request rebuilds Laravel's caches once its
 * answer has been sent, so nobody waits for it (see RefreshDeployCachesService).
 */
class RefreshDeployCaches
{
    public function __construct(private RefreshDeployCachesService $caches)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        // The health check says whether the caches and OPcache are on (yes / no only) and what pictures the server
        // can make light, to check the server from outside.
        if ($request->is('up')) {
            $response->headers->set('X-App-Cache', sprintf('config=%d; routes=%d; opcache=%d; images=%s; build=%s',
                app()->configurationIsCached(), app()->routesAreCached(), (int) (function_exists('opcache_get_status') && (@opcache_get_status(false)['opcache_enabled'] ?? false)),
                implode('+', \App\Support\ImageResizer::support()) ?: 'none', $this->caches->status()));
        }

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        // Only web requests: tests run in the console and some pretend to be production (they never build real caches).
        if (app()->isProduction() && ! app()->runningInConsole() && $this->caches->isStale()) {
            $this->caches->execute();
        }
    }
}
