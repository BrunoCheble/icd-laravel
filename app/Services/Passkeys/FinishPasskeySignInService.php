<?php

namespace App\Services\Passkeys;

use App\Models\Passkey;
use App\Models\User;
use lbuchs\WebAuthn\WebAuthnException;

class FinishPasskeySignInService
{
    public function __construct(private PasskeyCeremony $ceremony)
    {
    }

    /**
     * Checks the device's signature (challenge, origin, site, fingerprint / face / PIN used, signature count) with the
     * public key kept for that passkey, and returns its user. With `$expected` (unlocking), it must be that user's.
     */
    public function execute(array $credential, ?User $expected = null): User
    {
        $challenge = $this->ceremony->pullChallenge('sign-in');
        $id = $credential['id'] ?? null;
        $passkey = is_string($id) ? Passkey::with('user')->where('credential_id', $id)->first() : null;
        if (! $passkey || ! $passkey->user || ($expected && (int) $passkey->user_id !== (int) $expected->getKey())) {
            throw PasskeyCeremony::failure(__('This fingerprint is not registered here.'));
        }

        $clientDataJson = $this->ceremony->decode($credential['clientDataJSON'] ?? null);
        $this->ceremony->checkOrigin($clientDataJson);
        // The user the device keeps with the passkey is its owner here.
        if (! empty($credential['userHandle']) && ! hash_equals($this->ceremony->userHandle($passkey->user), $this->ceremony->decode($credential['userHandle']))) {
            throw PasskeyCeremony::failure();
        }

        $webAuthn = $this->ceremony->webAuthn();
        try {
            $webAuthn->processGet(
                $clientDataJson,
                $this->ceremony->decode($credential['authenticatorData'] ?? null),
                $this->ceremony->decode($credential['signature'] ?? null),
                $passkey->public_key,
                $challenge,
                $passkey->sign_count,
                true,
                true,
            );
        } catch (WebAuthnException) {
            throw PasskeyCeremony::failure();
        }

        $passkey->forceFill([
            'sign_count' => max($passkey->sign_count, (int) ($webAuthn->getSignatureCounter() ?? 0)),
            'last_used_at' => now(),
        ])->save();

        return $passkey->user;
    }
}
