<?php

namespace App\Enums;

class InvitedBy
{
    public const WHO = 'who';
    public const INSTAGRAM = 'instagram';
    public const FACEBOOK = 'facebook';
    public const YOUTUBE = 'youtube';
    public const GOOGLE = 'google';
    public const OTHERS = 'others';

    public static function options()
    {
        return [
            self::WHO => __('Who'),
            self::INSTAGRAM => 'Instagram',
            self::FACEBOOK => 'Facebook',
            self::YOUTUBE => 'YouTube',
            self::GOOGLE => 'Google',
            self::OTHERS => __('Others'),
        ];
    }
}

