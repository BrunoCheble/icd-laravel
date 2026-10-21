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
     * the church too), the ministries it is in (with its role) and what it still has to send when pending.
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
            'ministries' => $member->ministryMemberships()->with('ministry')->get()
                ->filter(fn ($membership) => $membership->ministry)
                ->sortBy([['active', 'desc'], fn ($a, $b) => strcmp($a->ministry->name, $b->ministry->name)])->values(),
        ];
    }
}
