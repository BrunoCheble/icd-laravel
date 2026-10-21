<?php

namespace Tests\Feature\Notifications;

use App\Support\WebPush;
use Tests\TestCase;

/**
 * The Web Push messages, read back as a device would read them (RFC 8291) and their VAPID signature checked.
 */
class WebPushTest extends TestCase
{
    private string $keyFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->keyFile = sys_get_temp_dir() . '/webpush-test-' . uniqid() . '.json';
    }

    protected function tearDown(): void
    {
        @unlink($this->keyFile);
        parent::tearDown();
    }

    public function test_the_server_keys_are_made_once_and_kept(): void
    {
        $push = new WebPush($this->keyFile);
        $key = $push->publicKey();

        $this->assertSame(65, strlen(WebPush::unbase64url($key)));
        $this->assertSame($key, (new WebPush($this->keyFile))->publicKey());
    }

    public function test_a_device_reads_the_message(): void
    {
        // The device's key and secret, as a browser makes them
        $device = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $ec = openssl_pkey_get_details($device)['ec'];
        $devicePublic = "\x04" . str_pad($ec['x'], 32, "\0", STR_PAD_LEFT) . str_pad($ec['y'], 32, "\0", STR_PAD_LEFT);
        $auth = random_bytes(16);

        $message = (new WebPush($this->keyFile))->encrypt('{"title":"Olá ✓"}', $devicePublic, $auth);

        // Header: salt, record size, the server's one-time key
        $salt = substr($message, 0, 16);
        $this->assertSame(4096, unpack('N', substr($message, 16, 4))[1]);
        $serverPublic = substr($message, 21, ord($message[20]));
        $body = substr($message, 21 + strlen($serverPublic));
        $shared = openssl_pkey_derive(openssl_pkey_get_public(WebPush::pem($serverPublic)), $device, 32);
        $hkdf = fn ($salt, $ikm, $info, $length) => substr(hash_hmac('sha256', $info . "\x01", hash_hmac('sha256', $ikm, $salt, true), true), 0, $length);
        $ikm = $hkdf($auth, $shared, "WebPush: info\0" . $devicePublic . $serverPublic, 32);
        $plain = openssl_decrypt(substr($body, 0, -16), 'aes-128-gcm', $hkdf($salt, $ikm, "Content-Encoding: aes128gcm\0", 16),
            OPENSSL_RAW_DATA, $hkdf($salt, $ikm, "Content-Encoding: nonce\0", 12), substr($body, -16));

        $this->assertSame("{\"title\":\"Olá ✓\"}\x02", $plain);
    }

    public function test_the_vapid_token_is_signed_by_the_server_key(): void
    {
        $push = new WebPush($this->keyFile);
        [$header, $claims, $signature] = explode('.', $push->jwt('https://fcm.googleapis.com/fcm/send/abc'));

        $this->assertSame('https://fcm.googleapis.com', json_decode(WebPush::unbase64url($claims), true)['aud']);
        // r and s back to DER, checked with the server's public key
        $raw = WebPush::unbase64url($signature);
        $int = fn ($n) => "\x02" . chr(strlen($n = (ord($n[0]) > 127 ? "\0" : '') . ltrim($n, "\0"))) . $n;
        $der = ($seq = $int(substr($raw, 0, 32)) . $int(substr($raw, 32))) ? "\x30" . chr(strlen($seq)) . $seq : '';
        $this->assertSame(1, openssl_verify("{$header}.{$claims}", $der, WebPush::pem(WebPush::unbase64url($push->publicKey())), OPENSSL_ALGO_SHA256));
    }
}
