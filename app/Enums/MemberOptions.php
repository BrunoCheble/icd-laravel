<?php

namespace App\Enums;

class MemberOptions
{
    public const FIRST_NAME = 'first_name';
    public const LAST_NAME = 'last_name';
    public const EMAIL = 'email';
    public const PHONE_NUMBER = 'phone_number';
    public const ADDRESS = 'address';
    public const CITY = 'city';
    public const ZIP_CODE = 'zip_code';
    public const GENDER = 'gender';

    public static function options()
    {
        return [
            self::FIRST_NAME => 'First Name',
            self::LAST_NAME => 'Last Name',
            self::EMAIL => 'Email',
            self::PHONE_NUMBER => 'Phone Number',
            self::ADDRESS => 'Address',
            self::CITY => 'City',
            self::ZIP_CODE => 'Zip Code',
            self::GENDER => 'Gender',
        ];
    }
}

