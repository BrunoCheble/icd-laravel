<?php

namespace App\Modules\Members\Services;

use App\Modules\Members\Models\Member;

class GetMemberPendingInformationService
{
    /**
     * What a pending member (registered on the site) still has to send, to ask by WhatsApp. Empty for other members.
     *
     * @return string[]
     */
    public function execute(Member $member): array
    {
        $pendingInformations = [];

        if (!$member->isPending()) {
            return $pendingInformations;
        }

        if ($member->marital_status == 'M' && !$member->spouse_id) {
            $pendingInformations[] = $member->gender == 'M' ? 'Nome completo da esposa' : 'Nome completo do marido';
        }

        if ($member->marital_status == 'M' && !$member->date_joined) {
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

        if (!$member->document_number && (!$member->date_of_birth || $member->years() >= 18)) {
            $pendingInformations[] = 'NIF (para associar as ofertas e dízimos)';
        }

        return $pendingInformations;
    }
}
