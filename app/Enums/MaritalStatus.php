<?php

namespace App\Enums;

class MaritalStatus
{
    public const SINGLE = 'Single';
    public const MARRIED = 'Married';
    public const DIVORCED = 'Divorced';
    public const WIDOWED = 'Widowed';

    public static function options()
    {
        return [
            'S' => __('Single'),
            'M' => __('Married'),
            'D' => __('Divorced'),
            'W' => __('Widowed'),
        ];
    }

    public static function getIndexByValue($value)
    {
        return array_search($value, self::options());
    }
}
