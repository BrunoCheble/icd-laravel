<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The system (admin.igrejaicd.pt) stays out of the search engines: its pages are for the church's team and the
 * site's forms, and the church is found by its site (igrejaicd.pt). robots.txt lets them in so they read this.
 */
class PreventIndexing
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
