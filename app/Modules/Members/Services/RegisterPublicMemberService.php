<?php

namespace App\Modules\Members\Services;

use App\Enums\MembershipStatus;
use App\Helpers\NameHelper;
use App\Modules\Members\Models\Member;
use Carbon\Carbon;

class RegisterPublicMemberService
{
    /**
     * A member registered from the church's site: waiting for the team (pending), with the name split and written
     * as names are, and the full address.
     */
    public function execute(array $data): Member
    {
        $name = NameHelper::splitFullName($data['full_name']);

        return Member::create([
            'first_name' => $name['first_name'],
            'middle_name' => $name['middle_name'],
            'last_name' => $name['last_name'],
            'gender' => $data['gender'],
            'date_of_birth' => Carbon::createFromFormat('d/m/Y', $data['birthdate'])->toDateString(),
            'document_number' => $data['document_number'],
            'marital_status' => $data['marital_status'],
            'phone_number' => $data['phone_number'],
            'email' => $data['email'],
            'zip_code' => $data['zip_code'],
            'address' => trim(NameHelper::normalizeName($data['address']) . ' ' . $data['address_number']),
            'city' => NameHelper::normalizeName($data['city']),
            'membership_status' => MembershipStatus::PENDING,
        ]);
    }
}
