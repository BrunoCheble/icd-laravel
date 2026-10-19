<?php

namespace App\Modules\Members\Services;

use App\Enums\MembershipStatus;
use App\Modules\Members\Models\Member;

class RemoveMemberService
{
    public const DEACTIVATED = 'deactivated';
    public const DELETED = 'deleted';

    /**
     * An active member is only deactivated, keeping the reason in the notes; a pending one (registered on the site,
     * never accepted) is deleted. Inactive members stay as they are (null).
     */
    public function execute(Member $member, ?string $reason = null): ?string
    {
        if ($member->isActive()) {
            $member->membership_status = MembershipStatus::INACTIVED;
            $member->notes .= ' - Motivo da desativação: ' . trim((string) $reason);
            $member->save();
            return self::DEACTIVATED;
        }

        if ($member->isPending()) {
            $member->delete();
            return self::DELETED;
        }

        return null;
    }
}
