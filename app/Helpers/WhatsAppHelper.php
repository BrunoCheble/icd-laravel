<?php

namespace App\Helpers;

class WhatsAppHelper
{
    /**
     * The wa.me link of a phone number (null without one): WhatsApp wants the country code, so Portuguese numbers
     * (9 digits) get 351. With a text, the message comes already written.
     */
    public static function url(?string $phone, ?string $text = null): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        $digits = str_starts_with($digits, '00') ? substr($digits, 2) : $digits;
        if ($digits === '') {
            return null;
        }

        $url = 'https://wa.me/' . (strlen($digits) === 9 ? '351' . $digits : $digits);

        return $text === null ? $url : $url . '?text=' . rawurlencode($text);
    }
}
