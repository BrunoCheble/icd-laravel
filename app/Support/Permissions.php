<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * What a user of the admin may do, by module (see users.permissions: {"members": ["read", "write"], …}).
 * Administrators may do everything, and only they manage the users. A page's module comes from its route's name,
 * its action from the request: seeing (GET), creating (POST and the "create" form), editing (PUT / PATCH and the
 * "edit" form) and deleting (DELETE).
 */
class Permissions
{
    public const ACTIONS = ['read' => 'Can see', 'write' => 'Can create', 'update' => 'Can edit', 'delete' => 'Can delete'];

    // Module => [label, the route names it covers ("x.*": every route starting with "x."), the actions it has].
    public const MODULES = [
        'dashboard' => ['Dashboard', ['dashboard', 'dashboard.*'], ['read']],
        'members' => ['Members', ['members.*', 'ministries.*', 'ministries-members.*', 'report.families.*'], ['read', 'write', 'update', 'delete']],
        'visitors' => ['Visitors', ['visitors.*'], ['read', 'write', 'update', 'delete']],
        'anniversaries' => ['Anniversaries', ['report.anniversaries.*'], ['read']],
        'announcements' => ['Announcements', ['announcements.*'], ['read', 'write', 'update', 'delete']],
        'prayers' => ['Prayer Requests', ['prayers.*'], ['read', 'delete']],
        'songs' => ['Songs', ['songs.*'], ['read', 'write', 'update', 'delete']],
        'setlists' => ['Setlists', ['setlists.*'], ['read', 'write', 'update', 'delete']],
        'suggestions' => ['Suggestions', ['song-suggestions.*'], ['read', 'update']],
        'discipleships' => ['Discipleships', ['discipleships.*'], ['read', 'write', 'update', 'delete']],
    ];

    // Pages every signed-in user reaches (their own account, sign-in and lock).
    public const FREE = ['profile.*', 'passkeys.*', 'password.*', 'push.*', 'lock', 'lock.*', 'logout', 'verification.*'];

    // Routes whose action is not the one of their method: changes asked with GET or POST, the parts of a song
    // (bars, sheet, lessons, versions…: editing it) and tools that only read ("x.*" as above; the first match).
    private const ACTION_OF_ROUTE = [
        'members.activate' => 'update',
        'visitors.activate' => 'update',
        'ministries-members.*' => 'update',
        'dashboard.*' => 'read',
        'announcements.approval' => 'update',
        'announcements.comments.*' => 'update',
        'song-suggestions.*' => 'update',
        'songs.chord-sheet.parse' => 'read',
        'songs.chord-sheet.structure' => 'read',
        'songs.bulk-destroy' => 'delete',
        'songs.bars.*' => 'update',
        'songs.sheet.*' => 'update',
        'songs.sheet-section.*' => 'update',
        'songs.layout.*' => 'update',
        'songs.lessons.*' => 'update',
        'songs.versions.*' => 'update',
        'songs.history.restore' => 'update',
        'songs.chord-sheet.import' => 'update',
        'setlists.import' => 'write',
        'discipleships.meetings.outcome' => 'update',
        'discipleships.transfer' => 'update',
        'discipleships.meetings.comments.*' => 'update',
    ];

    /** The module of a route name, or null when it is none of them. */
    public static function moduleOf(?string $route): ?string
    {
        foreach (self::MODULES as $module => [, $patterns]) {
            foreach ($patterns as $pattern) {
                if (self::matches($route, $pattern)) {
                    return $module;
                }
            }
        }

        return null;
    }

    public static function isFree(?string $route): bool
    {
        return collect(self::FREE)->contains(fn ($pattern) => self::matches($route, $pattern));
    }

    /** What the request does: read, write, update or delete. */
    public static function actionOf(Request $request): string
    {
        $route = (string) $request->route()?->getName();
        // A page (GET) of the "x.*" routes is still seen as usual (its "edit" form: editing).
        $reading = $request->isMethod('GET') || $request->isMethod('HEAD');
        foreach (self::ACTION_OF_ROUTE as $pattern => $action) {
            if (self::matches($route, $pattern) && ! ($reading && str_ends_with($pattern, '*'))) {
                return $action;
            }
        }

        return match (true) {
            $request->isMethod('DELETE') => 'delete',
            $request->isMethod('PUT') || $request->isMethod('PATCH') || str_ends_with($route, '.edit') => 'update',
            $request->isMethod('POST') || str_ends_with($route, '.create') => 'write',
            default => 'read',
        };
    }

    /** Whether the user may do the action in the module (administrators: always). */
    public static function allows(?User $user, string $module, string $action = 'read'): bool
    {
        if (! $user) {
            return false;
        }
        if ($user->is_admin) {
            return true;
        }

        return in_array($action, $user->permissions[$module] ?? [], true);
    }

    /** Only the known modules and actions, e.g. from the users form. */
    public static function clean(?array $permissions): array
    {
        $clean = [];
        foreach (self::MODULES as $module => $info) {
            $actions = array_values(array_intersect($info[2], (array) ($permissions[$module] ?? [])));
            // Creating, editing or deleting needs seeing.
            if ($actions && ! in_array('read', $actions, true)) {
                array_unshift($actions, 'read');
            }
            if ($actions) {
                $clean[$module] = $actions;
            }
        }

        return $clean;
    }

    /** The first page the user may see (where a sign-in goes when the dashboard is not theirs). */
    public static function homeFor(User $user): string
    {
        foreach (['dashboard' => 'dashboard', 'members' => 'members.index', 'visitors' => 'visitors.index', 'anniversaries' => 'report.anniversaries.index',
            'announcements' => 'announcements.index', 'prayers' => 'prayers.index', 'songs' => 'songs.index', 'setlists' => 'setlists.index',
            'suggestions' => 'song-suggestions.index', 'discipleships' => 'discipleships.index'] as $module => $route) {
            if (self::allows($user, $module) && Route::has($route)) {
                return route($route);
            }
        }

        return route('profile.edit');
    }

    private static function matches(?string $route, string $pattern): bool
    {
        if ($route === null) {
            return false;
        }

        return str_ends_with($pattern, '*') ? str_starts_with($route, substr($pattern, 0, -1)) : $route === $pattern;
    }
}
