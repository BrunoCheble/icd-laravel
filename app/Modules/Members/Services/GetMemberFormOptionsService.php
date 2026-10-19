<?php

namespace App\Modules\Members\Services;

use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\MembershipStatus;
use App\Helpers\MemberHelper;
use App\Modules\Members\Models\Member;

class GetMemberFormOptionsService
{
    /**
     * What the member form offers: genders, marital and membership statuses, and the other members that can be the
     * spouse, father or mother (adults, or of unknown age): [id, name, document, gender].
     */
    public function execute(?Member $member = null): array
    {
        $people = Member::query()
            ->when($member?->id, fn ($query) => $query->where('id', '!=', $member->id))
            ->orderBy('first_name')->orderBy('last_name')
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'document_number', 'gender', 'date_of_birth'])
            ->filter(fn (Member $person) => $person->years() === null || $person->years() >= MemberHelper::MIN_AGE)
            ->map(fn (Member $person) => [
                'id' => (string) $person->id,
                'name' => $person->full_name,
                'document' => $person->document_number,
                'gender' => $person->gender,
            ])
            ->values()
            ->all();

        return [
            'genders' => Gender::options(),
            'maritalStatus' => MaritalStatus::options(),
            'membershipStatus' => MembershipStatus::options(),
            'people' => $people,
        ];
    }
}
