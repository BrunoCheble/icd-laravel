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
            self::ACTIVED => 'Actived',
            self::INACTIVED => 'Inactived',
            self::PENDING => 'Pending',
        ];
    }
}
