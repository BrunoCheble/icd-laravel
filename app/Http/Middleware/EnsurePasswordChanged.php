<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signed in with a temporary password (sent by WhatsApp): first a password of one's own, then the rest.
 */
class EnsurePasswordChanged
{
    // What stays reachable meanwhile.
    private const ALLOWED = ['password.change', 'password.change.update', 'logout', 'lock', 'lock.*'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->must_change_password || $request->routeIs(...self::ALLOWED) || ! in_array('auth', $request->route()?->gatherMiddleware() ?? [], true)) {
            return $next($request);
        }

        return $request->expectsJson()
            ? response()->json(['message' => __('Change your temporary password first.')], 403)
            : redirect()->route('password.change');
    }
}
