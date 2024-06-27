<?php

namespace App\Enums;

class Gender
{
    public const MALE = 'Male';
    public const FEMALE = 'Female';

    public static function options()
    {
        return [
            'M' => __(self::MALE),
            'F' => __(self::FEMALE),
        ];
    }

    public static function getIndexByValue($value)
    {
        return array_search($value, self::options());
    }
}
