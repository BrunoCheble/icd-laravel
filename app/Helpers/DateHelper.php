<?php

namespace App\Helpers;

use DateTime;

class DateHelper
{
    public static function formatStringToDate($date) {
        if (!preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
            return null;
        }

        [$day, $month, $year] = explode('/', $date);

        if (!checkdate((int)$month, (int)$day, (int)$year)) {
            return null;
        }

        return (new DateTime("$year-$month-$day"))->format('Y-m-d');
    }

}
