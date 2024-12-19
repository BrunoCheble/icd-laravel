<?php

namespace App\Enums;

class VisitorGroup
{
    public const KID = 'kid';
    public const TEEN = 'teen';
    public const YOUTH = 'youth';
    public const ADULT = 'adult';
    public const COUPLE = 'couple';

    public static function options()
    {
        return [
            self::KID => __('Kid'),
            self::TEEN => __('Teen'),
            self::YOUTH => __('Youth'),
            self::ADULT => __('Adult'),
            self::COUPLE => __('Couple')
        ];
    }
}

