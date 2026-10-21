<?php

namespace App\Http\Middleware;

use App\Support\Permissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The admin's pages only for whom may: each signed-in page belongs to a module and asks an action (see
 * Permissions); the pages of no module (the users…) are for the administrators. The pages of one's own account,
 * the sign-in and the lock are for everyone. Without the dashboard, a sign-in goes to the first page the user has.
 */
class EnsurePermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $route = $request->route();
        if (! $user || ! $route || $user->is_admin || ! in_array('auth', $route->gatherMiddleware(), true)) {
            return $next($request);
        }

        $name = $route->getName();
        if (Permissions::isFree($name)) {
            return $next($request);
        }

        $module = Permissions::moduleOf($name);
        if ($module && Permissions::allows($user, $module, Permissions::actionOf($request))) {
            return $next($request);
        }

        if ($name === 'dashboard') {
            return redirect()->to(Permissions::homeFor($user));
        }

        abort(403, __('You do not have access to this page.'));
    }
}
