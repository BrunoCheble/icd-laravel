<?php

namespace App\Http\Middleware;

use App\Services\Notifications\SendPushDigestService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * In production, once an hour, after a request's answer was sent (nobody waits): the app's notifications (see
 * SendPushDigestService). A scheduled GitHub workflow calls /up every hour, so it runs even without visits.
 */
class RunPushDigest
{
    public function __construct(private SendPushDigestService $digest)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! app()->isProduction() || app()->runningInConsole() || ! $this->digest->isDue()) {
            return;
        }
        try {
            // One request at a time (the others go on).
            Cache::lock('push-digest', 120)->get(fn () => $this->digest->isDue() && $this->digest->execute());
        } catch (Throwable $error) {
            report($error);
        }
    }
}
