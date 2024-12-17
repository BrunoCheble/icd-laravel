<?php

namespace App\Enums;

class VisitorStatus
{
    public const ACTIVED = 'actived';
    public const INACTIVED = 'inactived';

    public static function options()
    {
        return [
            self::ACTIVED => __('Actived'),
            self::INACTIVED => __('Inactived'),
        ];
    }
}
