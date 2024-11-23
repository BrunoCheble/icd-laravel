<?php

namespace App\Helpers;

class NameHelper
{
    public static function splitFullName($fullName)
    {
        $fullName = self::normalizeName($fullName);
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

    public static function normalizeName($name) {
        $normalized = mb_convert_case(strtolower($name), MB_CASE_TITLE, "UTF-8");
        $lowercaseWords = ['de', 'da', 'do', 'das', 'dos', 'e'];
        $words = explode(' ', $normalized);
        foreach ($words as &$word) {
            if (in_array(strtolower($word), $lowercaseWords)) {
                $word = strtolower($word);
            }
        }
        return implode(' ', $words);
    }

}
