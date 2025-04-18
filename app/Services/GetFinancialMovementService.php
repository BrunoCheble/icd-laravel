<?php

use App\Enums\FinancialMovementOptions;
use App\Models\FinancialCategory;
use App\Models\FinancialMovement;
use App\Models\Member;
use App\Models\Ministry;

class GetFinancialMovementService
{
    static function execute($attribute, $sort, $order, $date_type, $date_from, $date_to) {

        $search = null;

        if ($attribute === FinancialMovementOptions::MEMBER) {
            $search = Member:: where('document_number', $attribute)->get()->first()->id;
        } elseif ($attribute === FinancialMovementOptions::MINISTRY) {
            $search = Ministry:: where('name', $attribute)->get()->first()->id;
        } elseif ($attribute === FinancialMovementOptions::CATEGORY) {
            $search = FinancialCategory:: where('name', $attribute)->get()->first()->id;
        }

        $transacations = FinancialMovement::filter($attribute, $search)->orderBy($sort ?? 'created_at', $order ?? 'desc');

        if (!$date_type || !$date_from || !$date_to) {
            return $transacations->get();
        }

        $transacations->whereBetween($date_type, [$date_from, $date_to]);

        return $transacations->get();
    }
}
