<?php

namespace App\Enums;

class VisitorOptions
{
    public const NAME = 'name';
    public const GENDER = 'gender';
    public const PHONE_NUMBER = 'phone_number';
    public const INVITED_BY = 'invited_by';
    public const GROUP = 'group';
    public const CITY = 'city';
    public const OBSERVATION = 'observation';

    public static function options()
    {
        return [
            self::NAME => __('Name'),
            self::GENDER => __('Gender'),
            self::PHONE_NUMBER => __('Phone Number'),
            self::INVITED_BY => __('Invited By'),
            self::GROUP => __('Group'),
            self::CITY => __('City'),
            self::OBSERVATION => __('Observation'),
        ];
    }
}

