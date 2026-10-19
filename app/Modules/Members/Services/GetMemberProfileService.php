<?php

namespace App\Modules\Members\Services;

use App\Modules\Members\Models\Member;

class GetMemberProfileService
{
    public function __construct(private GetMemberPendingInformationService $pending)
    {
    }

    /**
     * What the member's page shows besides the member: its family (spouse, father, mother and children, members of
     * the church too) and what it still has to send when pending.
     */
    public function execute(Member $member): array
    {
        $member->loadMissing(['spouse', 'father', 'mother']);

        return [
            'children' => Member::query()
                ->where(fn ($query) => $query->where('father_id', (string) $member->id)->orWhere('mother_id', (string) $member->id))
                ->orderBy('date_of_birth')
                ->get(),
            'pending' => $this->pending->execute($member),
        ];
    }
}
