<?php

namespace App\Services\Passkeys;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use lbuchs\WebAuthn\Binary\ByteBuffer;
use lbuchs\WebAuthn\WebAuthn;

/**
 * What the passkey steps share: the site as the relying party (its host), the one-time challenge kept in the session
 * between the two steps of a ceremony, base64url coding, and an exact check of the origin the device signed for.
 */
class PasskeyCeremony
{
    /** Seconds the device's prompt (and the challenge) stays valid. */
    public const TIMEOUT = 60;

    public function __construct(private Request $request)
    {
    }

    public function webAuthn(): WebAuthn
    {
        // Only the "none" attestation: no device model information is asked for.
        return new WebAuthn((string) config('app.name'), $this->rpId(), ['none'], true);
    }

    public function rpId(): string
    {
        return strtolower($this->request->getHost());
    }

    /** Keeps the challenge just sent to the device for `$purpose` ("register", "sign-in"). */
    public function putChallenge(string $purpose, WebAuthn $webAuthn): void
    {
        $this->request->session()->put("passkeys.{$purpose}", [
            'challenge' => base64_encode($webAuthn->getChallenge()->getBinaryString()),
            'at' => time(),
        ]);
    }

    /** The challenge sent for `$purpose`, used once: removed from the session, refused when old. */
    public function pullChallenge(string $purpose): string
    {
        $kept = $this->request->session()->pull("passkeys.{$purpose}");
        if (! is_array($kept) || time() - ($kept['at'] ?? 0) > self::TIMEOUT + 30) {
            throw self::failure(__('The fingerprint request expired. Try again.'));
        }

        return base64_decode($kept['challenge']);
    }

    public function decode(mixed $value): string
    {
        if (! is_string($value) || $value === '' || preg_match('/[^A-Za-z0-9_-]/', $value)) {
            throw self::failure();
        }

        return ByteBuffer::fromBase64Url($value)->getBinaryString();
    }

    public function encode(string $binary): string
    {
        return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
    }

    /**
     * The device signed for this very site: the origin's host must be the site's host (the library only checks that
     * it ends with it).
     */
    public function checkOrigin(string $clientDataJson): void
    {
        $origin = json_decode($clientDataJson)->origin ?? '';
        $host = strtolower((string) parse_url(is_string($origin) ? $origin : '', PHP_URL_HOST));
        if (! hash_equals($this->rpId(), $host)) {
            throw self::failure();
        }
    }

    /** The user's id as the device keeps it with the passkey: opaque, the same every time. */
    public function userHandle(User $user): string
    {
        return hash_hmac('sha256', 'passkey-user:' . $user->getKey(), (string) config('app.key'), true);
    }

    public static function failure(?string $message = null): ValidationException
    {
        return ValidationException::withMessages(['passkey' => $message ?? __('The fingerprint could not be checked. Try again.')]);
    }
}
