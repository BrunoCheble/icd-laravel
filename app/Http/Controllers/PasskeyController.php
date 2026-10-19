<?php

namespace App\Http\Controllers;

use App\Models\Passkey;
use App\Services\Passkeys\FinishPasskeyRegistrationService;
use App\Services\Passkeys\FinishPasskeySignInService;
use App\Services\Passkeys\SessionLock;
use App\Services\Passkeys\StartPasskeyRegistrationService;
use App\Services\Passkeys\StartPasskeySignInService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Signing in with fingerprint / Face ID (passkeys): a user registers a device on the profile (after the current
 * password) and then signs in with it, without typing the email or the password.
 */
class PasskeyController extends Controller
{
    /** Options to register this device (the current password first). */
    public function options(Request $request, StartPasskeyRegistrationService $service): JsonResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);

        return response()->json($service->execute($request->user()));
    }

    /** The device's answer: its passkey kept for the user. */
    public function store(Request $request, FinishPasskeyRegistrationService $service): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'credential' => ['required', 'array'],
        ]);
        $passkey = $service->execute($request->user(), $data['credential'], $data['name']);

        return response()->json(['id' => $passkey->id, 'name' => $passkey->name]);
    }

    public function destroy(Request $request, Passkey $passkey): RedirectResponse
    {
        abort_unless((int) $passkey->user_id === (int) $request->user()->getKey(), 404);
        $passkey->delete();

        return back()->with('status', 'passkey-removed');
    }

    /** Options to sign in with any passkey of this device for the site. */
    public function signInOptions(StartPasskeySignInService $service): JsonResponse
    {
        return response()->json($service->execute());
    }

    /** Signed in by the device's signature (and kept signed in on it; the app locks itself when away). */
    public function signIn(Request $request, FinishPasskeySignInService $service, SessionLock $lock): JsonResponse
    {
        $data = $request->validate(['credential' => ['required', 'array']]);
        $user = $service->execute($data['credential']);

        Auth::login($user, true);
        $request->session()->regenerate();
        $lock->unlock($request);

        return response()->json(['redirect' => redirect()->intended(route('dashboard'))->getTargetUrl()]);
    }
}
