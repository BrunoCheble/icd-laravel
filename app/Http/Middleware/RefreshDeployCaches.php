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
        // The health check says whether the caches and OPcache are on (yes / no only), to check the server from outside.
        if ($request->is('up')) {
            $response->headers->set('X-App-Cache', sprintf('config=%d; routes=%d; opcache=%d',
                app()->configurationIsCached(), app()->routesAreCached(), (int) (function_exists('opcache_get_status') && (@opcache_get_status(false)['opcache_enabled'] ?? false))));
        }

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        if (app()->isProduction() && $this->caches->isStale()) {
            $this->caches->execute();
        }
    }
}
