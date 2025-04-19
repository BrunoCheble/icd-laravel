<?php

namespace App\Enums;

class AnnouncementType
{
    public const RESUME = 'resume';
    public const SERVICE = 'service';
    public const DONATION = 'donation';
    public const PRODUCT = 'product';

    public static function options(): array
    {
        return [
            self::RESUME => __('Resume'),
            self::SERVICE => __('Service'),
            self::DONATION => __('Donation'),
            self::PRODUCT => __('Product'),
        ];
    }
}
