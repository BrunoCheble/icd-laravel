<?php

namespace App\Helpers;

use Illuminate\Support\Collection;

class MemberHelper
{
    public const MIN_AGE = 18;

    public static function getAdultWomen(Collection $members): Collection
    {
        return $members->filter(function ($member) {
            return $member->gender == 'F' && (!$member->age || $member->age >= self::MIN_AGE);
        });
    }

    public static function getAdultMen(Collection $members): Collection
    {
        return $members->filter(function ($member) {
            return $member->gender == 'M' && (!$member->age || $member->age >= self::MIN_AGE);
        });
    }

    public static function getSingleByOppositeGender(Collection $members, string|null $opposite_gender): Collection
    {
        return $members->filter(function ($member) use ($opposite_gender) {
            return $member->gender != $opposite_gender && (!$member->age || $member->age >= self::MIN_AGE);
        });
    }
}
