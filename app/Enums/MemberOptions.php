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
    public const DATE_OF_BIRTH = 'date_of_birth';
    public const MARITAL_STATUS = 'marital_status';
    public const BAPTISM_DATE = 'baptism_date';
    public const CREATE_AT = 'created_at';
    public const UPDATE_AT = 'updated_at';

    public static function options()
    {
        return [
            self::FIRST_NAME => __('First Name'),
            self::LAST_NAME => __('Last Name'),
            self::EMAIL => __('Email'),
            self::PHONE_NUMBER => __('Phone Number'),
            self::ADDRESS => __('Address'),
            self::CITY => __('City'),
            self::ZIP_CODE => __('Zip Code'),
            self::GENDER => __('Gender'),
            self::DATE_OF_BIRTH => __('Date of Birth'),
            self::MARITAL_STATUS => __('Marital Status'),
            self::BAPTISM_DATE => __('Baptism Date'),
            self::CREATE_AT => __('Created At'),
            self::UPDATE_AT => __('Updated At'),
        ];
    }
}

