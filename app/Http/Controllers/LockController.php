<?php

namespace App\Http\Controllers;

use App\Services\Passkeys\FinishPasskeySignInService;
use App\Services\Passkeys\SessionLock;
use App\Services\Passkeys\StartPasskeySignInService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * The app's lock (see SessionLock): the lock screen, unlocking with the fingerprint / face or the password, and the
 * page's sign that it is in use (so a page being worked on does not lock).
 */
class LockController extends Controller
{
    public function show(Request $request, SessionLock $lock): View|RedirectResponse
    {
        if (! $lock->applies($request) || ! $lock->isLocked($request)) {
            return redirect()->intended(route('dashboard'));
        }

        return view('auth.lock');
    }

    public function ping(Request $request, SessionLock $lock): JsonResponse|Response
    {
        if ($lock->applies($request) && $lock->isLocked($request)) {
            return response()->json(['locked' => true], 423);
        }
        $lock->touch($request);

        return response()->noContent();
    }

    public function options(Request $request, StartPasskeySignInService $service): JsonResponse
    {
        return response()->json($service->execute($request->user()));
    }

    public function unlock(Request $request, FinishPasskeySignInService $service, SessionLock $lock): JsonResponse
    {
        $data = $request->validate(['credential' => ['required', 'array']]);
        $service->execute($data['credential'], $request->user());
        $lock->unlock($request);

        return response()->json(['redirect' => redirect()->intended(route('dashboard'))->getTargetUrl()]);
    }

    public function password(Request $request, SessionLock $lock): JsonResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);
        $lock->unlock($request);

        return response()->json(['redirect' => redirect()->intended(route('dashboard'))->getTargetUrl()]);
    }
}
