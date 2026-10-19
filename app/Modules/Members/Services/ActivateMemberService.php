<?php

namespace App\Modules\Members\Services;

use App\Enums\MembershipStatus;
use App\Modules\Members\Models\Member;

class ActivateMemberService
{
    /**
     * Makes a pending (registered on the site) or inactive member active.
     */
    public function execute(Member $member): void
    {
        $member->membership_status = MembershipStatus::ACTIVED;
        $member->save();
    }
}
