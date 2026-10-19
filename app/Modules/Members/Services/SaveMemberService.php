<?php

namespace App\Modules\Members\Services;

use App\Modules\Members\Models\Member;

class SaveMemberService
{
    /**
     * Creates the member, or updates the one given (the observer links the spouse back, with the wedding date).
     */
    public function execute(array $data, ?Member $member = null): Member
    {
        if ($member) {
            $member->update($data);
            return $member;
        }

        return Member::create($data);
    }
}
