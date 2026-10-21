<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Web Push with PHP's OpenSSL (no library: the server has no Composer): the server's VAPID keys, made once and kept
 * in storage, the payload encrypted for the device (aes128gcm, RFC 8291) and signed for the push service (VAPID,
 * RFC 8292). The device's browser (Chrome on Android, Safari on an installed iPhone app) shows it, also with the app
 * closed.
 */
class WebPush
{
    // A P-256 public key's DER prefix (SubjectPublicKeyInfo), before its 65 bytes.
    private const P256_PREFIX = '3059301306072a8648ce3d020106082a8648ce3d030107034200';

    public function __construct(private ?string $keyFile = null)
    {
        $this->keyFile ??= storage_path('app/webpush-keys.json');
    }

    /** The server's keys: ["public" => base64url of the 65 bytes, "private" => PEM], made on first use. */
    public function keys(): array
    {
        if (is_file($this->keyFile)) {
            return json_decode((string) file_get_contents($this->keyFile), true);
        }
        [$private, $public] = $this->newKey();
        $keys = ['public' => self::base64url($public), 'private' => $private];
        if (! is_dir(dirname($this->keyFile))) {
            mkdir(dirname($this->keyFile), 0775, true);
        }
        file_put_contents($this->keyFile, json_encode($keys), LOCK_EX);
        @chmod($this->keyFile, 0600);

        return $keys;
    }

    public function publicKey(): string
    {
        return $this->keys()['public'];
    }

    /**
     * Sends the payload to a device; returns the push service's answer (201: delivered; 404 / 410: the device is no
     * longer subscribed).
     */
    public function send(string $endpoint, string $p256dh, string $auth, array $payload, int $ttl = 86400): int
    {
        $body = $this->encrypt(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), self::unbase64url($p256dh), self::unbase64url($auth));

        return Http::timeout(10)
            ->withHeaders([
                'Authorization' => 'vapid t=' . $this->jwt($endpoint) . ', k=' . $this->publicKey(),
                'Content-Encoding' => 'aes128gcm',
                'TTL' => (string) $ttl,
                'Urgency' => 'normal',
            ])
            ->withBody($body, 'application/octet-stream')
            ->post($endpoint)
            ->status();
    }

    /** The message for the device (RFC 8291): salt, record size, the server's one-time key, then the encrypted text. */
    public function encrypt(string $plaintext, string $devicePublic, string $authSecret): string
    {
        [$private, $public] = $this->newKey();
        $shared = openssl_pkey_derive(openssl_pkey_get_public(self::pem($devicePublic)), openssl_pkey_get_private($private), 32);
        if ($shared === false) {
            throw new RuntimeException('Web Push: the device key could not be used.');
        }
        $ikm = self::hkdf($authSecret, $shared, "WebPush: info\0" . $devicePublic . $public, 32);
        $salt = random_bytes(16);
        $key = self::hkdf($salt, $ikm, "Content-Encoding: aes128gcm\0", 16);
        $nonce = self::hkdf($salt, $ikm, "Content-Encoding: nonce\0", 12);
        // One record, the last one (delimiter 2).
        $cipher = openssl_encrypt($plaintext . "\x02", 'aes-128-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);

        return $salt . pack('N', 4096) . chr(strlen($public)) . $public . $cipher . $tag;
    }

    /** The VAPID token for the endpoint's push service (ES256, valid 12 hours). */
    public function jwt(string $endpoint): string
    {
        $parts = parse_url($endpoint);
        $claims = ['aud' => $parts['scheme'] . '://' . $parts['host'], 'exp' => time() + 43200, 'sub' => config('app.url')];
        $data = self::base64url('{"typ":"JWT","alg":"ES256"}') . '.' . self::base64url(json_encode($claims, JSON_UNESCAPED_SLASHES));
        openssl_sign($data, $der, $this->keys()['private'], OPENSSL_ALGO_SHA256);

        return $data . '.' . self::base64url(self::rawSignature($der));
    }

    /** A new P-256 key: [private PEM, public 65 bytes (uncompressed point)]. */
    private function newKey(): array
    {
        $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        openssl_pkey_export($key, $pem);
        $ec = openssl_pkey_get_details($key)['ec'];

        return [$pem, "\x04" . str_pad($ec['x'], 32, "\0", STR_PAD_LEFT) . str_pad($ec['y'], 32, "\0", STR_PAD_LEFT)];
    }

    private static function hkdf(string $salt, string $ikm, string $info, int $length): string
    {
        $prk = hash_hmac('sha256', $ikm, $salt, true);

        return substr(hash_hmac('sha256', $info . "\x01", $prk, true), 0, $length);
    }

    /** A P-256 public key (65 bytes) as PEM. */
    public static function pem(string $point): string
    {
        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode(hex2bin(self::P256_PREFIX) . $point), 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    /** OpenSSL's DER signature as JWT wants it: r and s, 32 bytes each. */
    private static function rawSignature(string $der): string
    {
        $offset = 3;
        $r = substr($der, $offset + 1, ord($der[$offset]));
        $offset += 1 + ord($der[$offset]) + 1;
        $s = substr($der, $offset + 1, ord($der[$offset]));
        $fit = fn ($n) => str_pad(ltrim($n, "\0"), 32, "\0", STR_PAD_LEFT);

        return $fit($r) . $fit($s);
    }

    public static function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function unbase64url(string $data): string
    {
        return (string) base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
    }
}
