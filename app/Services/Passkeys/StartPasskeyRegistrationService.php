<?php

namespace App\Services\Passkeys;

use App\Models\User;

class StartPasskeyRegistrationService
{
    public function __construct(private PasskeyCeremony $ceremony)
    {
    }

    /**
     * The options for the device to create a passkey for the user: kept on the device (to sign in without typing the
     * email), always asking for the fingerprint / face / PIN, and not again on a device already registered.
     */
    public function execute(User $user): object
    {
        $webAuthn = $this->ceremony->webAuthn();
        $registered = $user->passkeys()->pluck('credential_id')->map(fn (string $id) => $this->ceremony->decode($id))->all();
        $options = $webAuthn->getCreateArgs($this->ceremony->userHandle($user), $user->email, $user->name, PasskeyCeremony::TIMEOUT, true, true, null, $registered);
        $this->ceremony->putChallenge('register', $webAuthn);

        return $options;
    }
}
