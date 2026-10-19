<?php

namespace App\Services\Passkeys;

use App\Models\Passkey;
use App\Models\User;
use lbuchs\WebAuthn\WebAuthnException;

class FinishPasskeyRegistrationService
{
    public function __construct(private PasskeyCeremony $ceremony)
    {
    }

    /**
     * Checks the device's answer to the registration (challenge, origin, fingerprint / face / PIN used) and keeps its
     * public key for the user, named `$name` (e.g. "iPhone").
     */
    public function execute(User $user, array $credential, string $name): Passkey
    {
        $challenge = $this->ceremony->pullChallenge('register');
        $clientDataJson = $this->ceremony->decode($credential['clientDataJSON'] ?? null);
        $this->ceremony->checkOrigin($clientDataJson);

        try {
            $data = $this->ceremony->webAuthn()->processCreate(
                $clientDataJson,
                $this->ceremony->decode($credential['attestationObject'] ?? null),
                $challenge,
                true,
                true,
                false,
            );
        } catch (WebAuthnException) {
            throw PasskeyCeremony::failure();
        }

        $id = $this->ceremony->encode($data->credentialId);
        if (Passkey::where('credential_id', $id)->exists()) {
            throw PasskeyCeremony::failure(__('This device is already registered.'));
        }

        return $user->passkeys()->create([
            'name' => $name,
            'credential_id' => $id,
            'public_key' => $data->credentialPublicKey,
            'sign_count' => (int) ($data->signatureCounter ?? 0),
        ]);
    }
}
