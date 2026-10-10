<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The study app (icd-chords) sends the token of STUDY_API_TOKEN as "Authorization: Bearer <token>".
 * Without a token configured, the study API stays closed.
 */
class EnsureStudyToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) config('services.study.token');

        if ($token === '') {
            abort(503, 'The study API is not configured.');
        }
        if (! hash_equals($token, (string) $request->bearerToken())) {
            abort(401, 'Invalid token.');
        }

        return $next($request);
    }
}
