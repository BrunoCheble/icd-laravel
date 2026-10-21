<?php

namespace App\Modules\Ministries\Services;

use App\Modules\Members\Models\Member;
use App\Modules\Ministries\Models\Ministry;

class GetMinistryFormOptionsService
{
    /** The members not in the ministry yet, for the picker: [id, name, document]. */
    public function people(Ministry $ministry): array
    {
        return Member::query()->whereNotIn('id', $ministry->memberships()->select('member_id'))
            ->orderBy('first_name')->orderBy('last_name')
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'document_number'])
            ->map(fn (Member $member) => ['id' => (string) $member->id, 'name' => $member->full_name, 'document' => $member->document_number])
            ->values()->all();
    }
}
