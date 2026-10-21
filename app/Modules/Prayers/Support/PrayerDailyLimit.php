<?php

namespace App\Modules\Prayers\Support;

use App\Modules\Prayers\Models\Prayer;
use Illuminate\Support\Facades\RateLimiter;

/**
 * How many prayer requests the same person sends a day (anonymous, so three counts together, each over 24 hours):
 * this device (a random code the browser keeps), the mobile number given, and the internet connection (higher: at
 * the church many people share it). Past any of them, nothing is kept until the next day.
 */
class PrayerDailyLimit
{
    public const PER_DEVICE = 3;
    public const PER_PHONE = 3;
    public const PER_CONNECTION = 15;
    private const DAY = 86400;

    /** Whether one more request is still allowed. */
    public static function allows(?string $device, ?string $phone, ?string $ip): bool
    {
        if ($device && RateLimiter::tooManyAttempts(self::key('device', $device), self::PER_DEVICE)) {
            return false;
        }
        if ($ip && RateLimiter::tooManyAttempts(self::key('ip', $ip), self::PER_CONNECTION)) {
            return false;
        }

        return ! $phone || Prayer::query()->where('phone_number', $phone)->where('created_at', '>=', now()->subDay())->count() < self::PER_PHONE;
    }

    /** A request was kept: it counts for the device and the connection (the mobile number is counted in the table). */
    public static function hit(?string $device, ?string $ip): void
    {
        if ($device) {
            RateLimiter::hit(self::key('device', $device), self::DAY);
        }
        if ($ip) {
            RateLimiter::hit(self::key('ip', $ip), self::DAY);
        }
    }

    private static function key(string $kind, string $value): string
    {
        return "prayer-day:{$kind}:" . hash('sha256', $value);
    }
}
