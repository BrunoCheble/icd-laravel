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
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (app()->isProduction() && $this->caches->isStale()) {
            $this->caches->execute();
        }
    }
}
