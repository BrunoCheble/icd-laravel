<?php

namespace App\Modules\Music\Http\Middleware;

use App\Modules\Music\Models\Setlist;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The public repertoire changes a setlist only up to its day (Lisbon time): after it, it is only to be seen (the
 * chord suggestions, about the songs, are still taken).
 */
class EnsureSetlistNotPast
{
    public function handle(Request $request, Closure $next): Response
    {
        $setlist = $request->route('setlist');
        if ($setlist instanceof Setlist && $setlist->isPast()) {
            return response()->json(['message' => __('This setlist already took place: it can only be viewed.')], 403);
        }

        return $next($request);
    }
}
