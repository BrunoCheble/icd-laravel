<?php

namespace App\Services\Passkeys;

use App\Models\User;

class StartPasskeySignInService
{
    public function __construct(private PasskeyCeremony $ceremony)
    {
    }

    /**
     * The options for the device to sign in with a passkey, always asking for the fingerprint / face / PIN: any of the
     * device's passkeys for this site (signing in), or only `$user`'s (unlocking the app).
     */
    public function execute(?User $user = null): object
    {
        $webAuthn = $this->ceremony->webAuthn();
        $ids = $user ? $user->passkeys()->pluck('credential_id')->map(fn (string $id) => $this->ceremony->decode($id))->all() : [];
        $options = $webAuthn->getGetArgs($ids, PasskeyCeremony::TIMEOUT, true, true, true, true, true, true);
        $this->ceremony->putChallenge('sign-in', $webAuthn);

        return $options;
    }
}
