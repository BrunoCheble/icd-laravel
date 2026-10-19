<?php

namespace App\Http\Middleware;

use App\Services\Passkeys\SessionLock;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * On the signed-in pages (those behind "auth"), a locked session (see SessionLock) only reaches the lock screen and
 * sign-out: pages and forms go to the lock screen, requests of a page's scripts get 423 (the page shows its lock
 * over itself).
 */
class EnsureSessionUnlocked
{
    public function __construct(private SessionLock $lock)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        if (! $request->user() || ! $route || ! in_array('auth', $route->gatherMiddleware(), true)
            || $request->routeIs('lock', 'lock.*', 'logout') || ! $this->lock->applies($request)) {
            return $next($request);
        }

        if ($this->lock->isLocked($request)) {
            if ($request->expectsJson()) {
                return response()->json(['locked' => true, 'url' => route('lock')], 423);
            }

            // Back to this page once unlocked (a form sent while locked is not sent again).
            return $request->isMethod('GET') ? redirect()->guest(route('lock')) : redirect()->route('lock');
        }

        $this->lock->touch($request);

        return $next($request);
    }
}
