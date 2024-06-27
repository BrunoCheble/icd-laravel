<?php

namespace App\Helpers;

class NameHelper
{
    public static function splitFullName($fullName)
    {
        $nameParts = array_filter(explode(' ', trim($fullName)));

        $firstName = array_shift($nameParts);
        $lastName = array_pop($nameParts);
        $middleName = implode(' ', $nameParts);

        return [
            'first_name' => $firstName ?? '',
            'middle_name' => $middleName,
            'last_name' => $lastName ?? '',
        ];
    }
}
