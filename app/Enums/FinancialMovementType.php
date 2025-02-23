<?php

namespace App\Enums;

class FinancialMovementType
{
    public const EXPENSE = 'expense';
    public const INCOME = 'income';
    public const REFUND = 'refund';
    public const DISCOUNT = 'discount';
    public const TRANSFER = 'transfer';

    public static function options()
    {
        return [
            self::EXPENSE => __('Expense'),
            self::INCOME => __('Income'),
            self::REFUND => __('Refund'),
            self::DISCOUNT => __('Discount'),
            self::TRANSFER => __('Transfer'),
        ];
    }

    public static function getIndexByValue($value)
    {
        return array_search($value, self::options());
    }
}
