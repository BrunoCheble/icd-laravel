<?php

namespace App\Services;

use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Models\Member;

class GetMemberPendingInformationService
{
    static function execute(Member $member)
    {
        $pendingInformations = [];

        if (!$member->isPending()) {
            return $pendingInformations;
        }

        if ($member->marital_status == MaritalStatus::MARRIED && !$member->spouse_id) {
            $pendingInformations[] = $member->gender == Gender::MALE ? 'Nome completo da esposa' : 'Nome completo do marido';
        }

        if ($member->marital_status == MaritalStatus::MARRIED && !$member->date_joined) {
            $pendingInformations[] = 'Data de casamento';
        }

        if (!$member->email || !filter_var($member->email, FILTER_VALIDATE_EMAIL) ) {
            $pendingInformations[] = 'E-mail';
        }

        if (!$member->phone_number || !preg_match('/^(?!([0-9])\1{8})\d{9}$/', $member->phone_number)) {
            $pendingInformations[] = 'Número do telemovel';
        }

        if (!$member->address || !$member->zip_code || !$member->city) {
            $pendingInformations[] = 'Endereço, código postal e localidade';
        }

        if (!$member->date_of_birth) {
            $pendingInformations[] = 'Data de nascimento';
        }

        if (!$member->document_number && (!$member->date_of_birth || $member->age >= 18)) {
            $pendingInformations[] = 'NIF (para associar as ofertas e dízimos)';
        }

        return $pendingInformations;
    }
}
