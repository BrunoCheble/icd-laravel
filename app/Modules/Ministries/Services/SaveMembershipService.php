<?php

namespace App\Modules\Ministries\Services;

use App\Modules\Ministries\Models\MinistryMember;
use App\Modules\Ministries\Support\MinistryBio;

class SaveMembershipService
{
    /** A member's role, order, whether active and biography in the ministry. */
    public function execute(MinistryMember $membership, array $data): MinistryMember
    {
        $membership->fill([
            'ministry_role_id' => $data['ministry_role_id'] ?? null,
            // The text typed before the roles existed goes once a role is chosen.
            'role' => ($data['ministry_role_id'] ?? null) ? null : $membership->role,
            'active' => (bool) $data['active'],
            'sort_order' => (int) $data['sort_order'],
            'bio' => MinistryBio::clean($data['bio'] ?? null),
        ])->save();

        return $membership;
    }
}
