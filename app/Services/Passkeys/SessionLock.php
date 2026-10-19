<?php

namespace App\Services\Passkeys;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The app locks itself, like a bank app, for users who sign in with a passkey: after IDLE_MINUTES without use (or when
 * the session was restored by "remember me" after a long time away) it asks for the fingerprint / face (or the
 * password) again, without signing out. The session keeps the time of the last use and whether it is locked.
 */
class SessionLock
{
    public const IDLE_MINUTES = 15;

    private const LAST = 'lock.last_activity';

    private const LOCKED = 'lock.locked';

    /** Whether the signed-in user has the lock at all (only with a passkey to unlock it). */
    public function applies(Request $request): bool
    {
        $user = $request->user();
        if ($user === null) {
            return false;
        }
        try {
            return $user->passkeys()->exists();
        } catch (QueryException) {
            // The passkeys table not created yet (the site updated before its database): no lock, the admin still works.
            return false;
        }
    }

    public function isLocked(Request $request): bool
    {
        $session = $request->session();
        if ($session->get(self::LOCKED)) {
            return true;
        }
        $last = $session->get(self::LAST);
        if ($last === null) {
            // A session just restored by "remember me" starts locked; a sign-in just made starts the clock.
            if (Auth::viaRemember()) {
                $this->lock($request);

                return true;
            }
            $this->touch($request);

            return false;
        }
        if (time() - (int) $last > self::IDLE_MINUTES * 60) {
            $this->lock($request);

            return true;
        }

        return false;
    }

    public function touch(Request $request): void
    {
        $request->session()->put(self::LAST, time());
    }

    public function lock(Request $request): void
    {
        $request->session()->put(self::LOCKED, true);
    }

    public function unlock(Request $request): void
    {
        $request->session()->forget(self::LOCKED);
        $this->touch($request);
    }
}
