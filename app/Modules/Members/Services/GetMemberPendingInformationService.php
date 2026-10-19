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
        return array_values(array_map(fn ($field) => $this->label($field, $member), $this->fields($member)));
    }

    /**
     * The same, as the fields to fill: spouse, date_joined, email, phone_number, address (with zip code and city),
     * date_of_birth, document_number.
     *
     * @return string[]
     */
    public function fields(Member $member): array
    {
        if (! $member->isPending()) {
            return [];
        }

        $fields = [];
        // The spouse: a member (spouse_id) or, when not a member, the name (spouse_name).
        if ($member->marital_status == 'M' && ! $member->spouse_id && ! $member->spouse_name) {
            $fields[] = 'spouse';
        }
        if ($member->marital_status == 'M' && ! $member->date_joined) {
            $fields[] = 'date_joined';
        }
        if (! $member->email || ! filter_var($member->email, FILTER_VALIDATE_EMAIL)) {
            $fields[] = 'email';
        }
        if (! $member->phone_number || ! preg_match('/^(?!([0-9])\1{8})\d{9}$/', $member->phone_number)) {
            $fields[] = 'phone_number';
        }
        if (! $member->address || ! $member->zip_code || ! $member->city) {
            $fields[] = 'address';
        }
        if (! $member->date_of_birth) {
            $fields[] = 'date_of_birth';
        }
        if (! $member->document_number && (! $member->date_of_birth || $member->years() >= 18)) {
            $fields[] = 'document_number';
        }

        return $fields;
    }

    public function label(string $field, Member $member): string
    {
        return match ($field) {
            'spouse' => $member->gender == 'M' ? 'Nome completo e telemóvel da esposa' : 'Nome completo e telemóvel do marido',
            'date_joined' => 'Data de casamento',
            'email' => 'E-mail',
            'phone_number' => 'Número do telemovel',
            'address' => 'Endereço, código postal e localidade',
            'date_of_birth' => 'Data de nascimento',
            'document_number' => 'NIF (para associar as ofertas e dízimos)',
        };
    }
}
