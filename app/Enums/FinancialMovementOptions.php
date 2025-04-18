<?php

namespace App\Enums;

class FinancialMovementOptions
{
    public const DESCRIPTION = 'description';
    public const CATEGORY = 'category_id';
    public const MEMBER = 'member_id';
    public const MINISTRY = 'ministry_id';
    public const AMOUNT = 'amount';

    public static function options()
    {
        return [
            self::DESCRIPTION => __('Description'),
            self::CATEGORY => __('Category'),
            self::MEMBER => __('Member'),
            self::MINISTRY => __('Ministry'),
            self::AMOUNT => __('Amount'),
        ];
    }
}

