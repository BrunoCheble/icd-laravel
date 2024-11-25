<?php

namespace App\Enums;

class MembershipStatus
{
    public const ACTIVED = 'actived';
    public const INACTIVED = 'inactived';
    public const PENDING = 'pending';

    public static function options()
    {
        return [
            self::ACTIVED => __('Actived'),
            self::INACTIVED => __('Inactived'),
            self::PENDING => __('Pending'),
        ];
    }
}
