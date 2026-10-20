<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The system's pages open inside another page only on the system itself and on the church's site (its forms to
 * announce and to be a member, see ?embed=1), so no other site can wrap them to trick a click (clickjacking).
 * Locally, the site served from localhost too.
 */
class LimitFraming
{
    public const SITE = "'self' https://igrejaicd.pt https://www.igrejaicd.pt";

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if (! $response->headers->has('Content-Security-Policy')) {
            $response->headers->set('Content-Security-Policy', 'frame-ancestors ' . self::SITE . (app()->isLocal() ? ' http://localhost:*' : ''));
        }

        return $response;
    }
}
